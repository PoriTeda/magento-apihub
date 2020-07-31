<?php

namespace Riki\Loyalty\Model\Total\Quote;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;

class PointEarn extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * @var PriceCurrencyInterface
     */
    protected $_priceCurrency;

    /**
     * @var \Magento\SalesRule\Api\RuleRepositoryInterface
     */
    protected $_saleRuleRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchBuilder;

    /**
     * @var \Riki\Loyalty\Helper\Data
     */
    protected $_loyaltyHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $_appState;
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_adminQuoteSession;

    /**
     * PointEarn constructor.
     * @param PriceCurrencyInterface $priceCurrency
     * @param \Magento\SalesRule\Api\RuleRepositoryInterface $saleRulesRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Loyalty\Helper\Data $loyaltyHelper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(
        PriceCurrencyInterface $priceCurrency,
        \Magento\SalesRule\Api\RuleRepositoryInterface $saleRulesRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Loyalty\Helper\Data $loyaltyHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\State $appState,
        \Magento\Backend\Model\Session\Quote $quoteSession
    ) {
        $this->_priceCurrency = $priceCurrency;
        $this->_saleRuleRepository = $saleRulesRepository;
        $this->_searchBuilder = $searchCriteriaBuilder;
        $this->_loyaltyHelper = $loyaltyHelper;
        $this->_logger = $logger;
        $this->_appState = $appState;
        $this->_adminQuoteSession = $quoteSession;
        $this->setCode('earn_point');
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Address\Total $total
     * @return $this
     */
    public function collect(
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        /** @var \Magento\Quote\Model\Quote\Item $item */
        $items = $shippingAssignment->getItems();

        if (!count($items)) {
            return $this;
        }

        if ($quote->getSkipEarnPoint()) {
            return $this;
        }

        $promotions = [];
        $earnPoint = 0;
        if ($quote->getCustomer()->getId() && $this->allowEarnPoint($quote)) {
            $fixedPointApplied = [];
            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach ($items as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                $netSellingPrice = $this->_loyaltyHelper->netSellingPrice($item);

                $productPointDelta = $item->getProduct()->getData('point_currency');
                $appliedRuleIds = $item->getAppliedRuleIds();

                if ($productPointDelta || $appliedRuleIds) {

                    $additionalData = $item->getData('additional_data') ?: '{}';

                    try {
                        $additionalData = \Zend_Json::decode($additionalData);
                    } catch (\Zend_Json_Exception $e) {
                        $this->_logger->warning((string)$item->getData('additional_data'));
                        $this->_logger->warning($e);
                    }

                    if ($productPointDelta && $netSellingPrice) {
                        $productPointDelta = $productPointDelta / 100;
                        $pointAmount = floor($productPointDelta * $netSellingPrice);
                        $itemEarnPoint = ($pointAmount * $item->getQty());

                        if (is_array($additionalData)) {
                            $additionalData['earn_point'] = $itemEarnPoint;
                        }

                        // Don't collect earned point of oos item.
                        if (!$item->getData('is_oos_item')) {
                            $earnPoint += $itemEarnPoint;
                        }
                    }

                    if ($appliedRuleIds) {
                        foreach (explode(',', $appliedRuleIds) as $ruleId) {
                            /** @var \Riki\SalesRule\Model\Data\Rule $rule */
                            if (!isset($promotions[$ruleId])) {
                                try {
                                    $promotions[$ruleId] = $this->_saleRuleRepository->getById($ruleId);
                                } catch (\Exception $e) {
                                    $this->_logger->critical($e);
                                    continue;
                                }
                            }
                            $rule = $promotions[$ruleId];
                            $extensionAttributes = $rule->getExtensionAttributes();
                            $type = $extensionAttributes->getTypeBy();
                            if (!$type) {
                                continue;
                            }
                            $pointDelta = $extensionAttributes->getPointsDelta();
                            if (!$pointDelta) {
                                continue;
                            }
                            switch ($type) {
                                case 'riki_type_fixed':
                                    if (!in_array($ruleId, $fixedPointApplied)) {
                                        $earnPoint += floor($pointDelta);
                                        $fixedPointApplied[] = $ruleId;
                                    }
                                    break;
                                case 'riki_type_percent':
                                    $pointPercent = $pointDelta/100;
                                    $pointAmount = floor($pointPercent * $netSellingPrice);
                                    // Don't collect earned point of oos item.
                                    if (!$item->getData('is_oos_item')) {
                                        $earnPoint += $pointAmount * $item->getQty();
                                    }

                                    if (is_array($additionalData) && $pointAmount) {

                                        if (!isset($additionalData['earn_rule_point'])) {
                                            $additionalData['earn_rule_point'] = [];
                                        }

                                        $additionalData['earn_rule_point'][$ruleId] = [
                                            'point' =>  $pointAmount,
                                            'wbs_shopping_point'    =>  $rule->getWbsShoppingPoint(),
                                            'account_code'    =>  $rule->getAccountCode(),
                                            'point_expiration_period'    =>  $rule->getPointExpirationPeriod()
                                        ];
                                    }

                                    break;
                                default:
                                    break;
                            }
                        }
                    }

                    if (is_array($additionalData)) {
                        $item->setData('additional_data', \Zend_Json::encode($additionalData));
                    }
                }
            }
        }

        $total->setBonusPointAmount($earnPoint);
        $quote->setBonusPointAmount($earnPoint);

        return $this;
    }

    /**
     * Retrieve reward total data and set it to quote address
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param Address|Address\Total $total
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        if (
            $quote->getBonusPointAmount() ||
            $this->_appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE
        ) {
            return [
                'code' => $this->getCode(),
                'title' => __('Total Earned Point for this Order'),
                'value' => $quote->getBonusPointAmount(),
                'area'  =>  'footer'
            ];
        }
        return null;
    }

    /**
     * Check this quote is allow earn point or not
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return boolean
     */
    private function allowEarnPoint($quote)
    {
        if (($this->_appState->getAreaCode() == 'adminhtml' || $this->_appState->getAreaCode() == 'crontab') && !$quote->getAllowedEarnedPoint()) {

            // Checked Earn Reward Point to customer
            if ($this->_adminQuoteSession && $this->_adminQuoteSession->getAllowedEarnedPoint() == 1 ) {
                return true;
                }
            return false;
        }
        return true;
    }
}
