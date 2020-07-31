<?php

namespace Riki\Loyalty\Model\Total\Quote;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Riki\Loyalty\Model\ConsumerDb\ShoppingPoint;
use Riki\Loyalty\Model\RewardQuote;
use Riki\Subscription\Model\Emulator\Cart as CartEmulator;

class Reward extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $_rewardManagement;

    /**
     * @var ShoppingPoint
     */
    protected $_shoppingPoint;

    /**
     * @var \Riki\Loyalty\Model\QuoteFactory
     */
    protected $_rewardQuoteFactory;

    /**
     * @var \Riki\Subscription\Model\Emulator\Point\RewardQuoteFactory
     */
    protected $_emulatorRewardQuoteFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $_appState;
    /**
     * Helper data
     *
     * @var \Bluecom\PaymentFee\Helper\Data
     */
    protected $_helperData;
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_quoteBackendSession;
    /**
     * @var \Riki\Loyalty\Helper\Data
     */
    protected $_dataPointHelper;
    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $_customerRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * The flag resource.
     *
     * @var \Magento\Framework\FlagManager
     */
    private $flagManager;

    /**
     * Reward constructor.
     * @param \Riki\Loyalty\Model\RewardManagement $rewardManagement
     * @param ShoppingPoint $shoppingPoint
     * @param \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory
     * @param \Riki\Subscription\Model\Emulator\Point\RewardQuoteFactory $emulatorRewardQuoteFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\State $appState
     * @param \Bluecom\PaymentFee\Helper\Data $helperData
     * @param \Magento\Backend\Model\Session\Quote $quoteBackendSession
     * @param \Riki\Loyalty\Helper\Data $dataPointHelper
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $shoppingPoint,
        \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory,
        \Riki\Subscription\Model\Emulator\Point\RewardQuoteFactory $emulatorRewardQuoteFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\State $appState,
        \Bluecom\PaymentFee\Helper\Data $helperData,
        \Magento\Backend\Model\Session\Quote $quoteBackendSession,
        \Riki\Loyalty\Helper\Data $dataPointHelper,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\FlagManager $flagManager
    ) {
        $this->_rewardManagement = $rewardManagement;
        $this->_shoppingPoint = $shoppingPoint;
        $this->_rewardQuoteFactory = $rewardQuoteFactory;
        $this->_emulatorRewardQuoteFactory = $emulatorRewardQuoteFactory;
        $this->_coreRegistry = $registry;
        $this->_appState = $appState;
        $this->_quoteBackendSession = $quoteBackendSession;
        $this->_helperData = $helperData;
        $this->_dataPointHelper = $dataPointHelper;
        $this->_customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->flagManager = $flagManager;
        $this->setCode('apply_point');
    }

    /**
     * Collect reward point total
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Address\Total $total
     * @return $this
     * @throws LocalizedException
     */
    public function collect(
        Quote $quote,
        \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment,
        \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        $total->setUsedPoint(0)->setUsedPointAmount(0)->setBaseUsedPointAmount(0)->setFee(0)->setBaseFee(0);
        $customerCode = $this->getCustomerCode($quote);
        if (!$customerCode || $quote->getSkipUsePoint()) {
            return $this;
        }

        if ($quote->getSkipCollectDiscountFlag()) {
            return $this;
        }

        /** @var RewardQuote $rewardQuote */
        $rewardQuote = $this->_rewardQuoteFactory->create();

        if ($quote instanceof CartEmulator) {
            if (!$quote->hasData('reward_user_setting')) {
                return $this;
            }

            $rewardQuote->setData('reward_user_setting', $quote->getData('reward_user_setting'))
                ->setData('reward_user_redeem', $quote->getData('reward_user_redeem'));
        } else {
            $rewardQuote->load($quote->getId(), 'quote_id');

            if (!$rewardQuote->getId()) {
                $hasPointForTrial = ($quote->getData('point_for_trial') > 0 )?true:false;
                if ($hasPointForTrial) {
                    $flagcode = $quote->getId() .'_'.$quote->getCustomerId().'_'.$quote->getRikiCourseId();
                    $rewardData = $this->flagManager->getFlagData($flagcode);
                    if ($rewardData) {
                        $rewardQuote->setData($rewardData);
                    }
                } else {
                    return $this;
                }
            }
        }

        if ($customerCode && $total->getBaseGrandTotal() > 0) {
            $totalBalance = $this->getCurrentPointBalance($quote);

            switch ($rewardQuote->getData('reward_user_setting')) {
                case RewardQuote::USER_DO_NOT_USE_POINT:
                    // Back to other payment method from payment free
                    $this->_resetFee($quote, $total, 1);
                    return $this;
                case RewardQuote::USER_USE_SPECIFIED_POINT:
                    $redeemSetting = (int) $rewardQuote->getData('reward_user_redeem');
                    $rewardPoint = min($redeemSetting, $totalBalance);
                    break;
                case RewardQuote::USER_USE_ALL_POINT:
                    $rewardPoint = $totalBalance;
                    break;
                default:
                    return $this;
            }
            if (!$rewardPoint) {
                return $this;
            }
            $rewardAmount = $this->_rewardManagement->convertPointToAmount($rewardPoint);
            $baseCODFee = $this->_getBaseCodeFee($quote);
            $baseGrandTotal = $total->getBaseGrandTotal() - $quote->getBaseFee();
            if ($baseGrandTotal < 0.0001) {
                return $this;
            }

            if ($rewardAmount >= $baseGrandTotal) {
                $pointsCurrencyAmountUsed = $baseGrandTotal;
                $basePointsCurrencyAmountUsed = $baseGrandTotal;
                $usedPoint = (int) $pointsCurrencyAmountUsed;
                $total->setGrandTotal(0);
                $total->setBaseGrandTotal(0);
                $total->setFee(0);
                $total->setBaseFee(0);
                $quote->setFee(0);
                $quote->setBaseFee(0);

                $total->setTotalAmount('fee', 0);
                $total->setBaseTotalAmount('base_fee', 0);
            } elseif ($rewardAmount > 0) {
                // Back to other payment method from payment free
                $this->_resetFee($quote, $total);
                $pointEstimation = $baseGrandTotal + $quote->getBaseFee() - $baseCODFee;
                $pointsCurrencyAmountUsed = min($rewardAmount, $pointEstimation);
                $basePointsCurrencyAmountUsed = min($rewardAmount, $pointEstimation);
                $usedPoint = (int) $pointsCurrencyAmountUsed;
                $total->setGrandTotal($total->getGrandTotal() - $pointsCurrencyAmountUsed);
                $total->setBaseGrandTotal($total->getBaseGrandTotal() - $basePointsCurrencyAmountUsed);
            } else {
                return $this;
            }

            $quote->setUsedPoint($quote->getUsedPoint() + $usedPoint);
            $quote->setUsedPointAmount($quote->getUsedPointAmount() + $pointsCurrencyAmountUsed);
            $quote->setBaseUsedPointAmount($quote->getBaseUsedPointAmount() + $basePointsCurrencyAmountUsed);

            $total->setUsedPoint($usedPoint);
            $total->setUsedPointAmount($pointsCurrencyAmountUsed);
            $total->setBaseUsedPointAmount($basePointsCurrencyAmountUsed);
        }
        return $this;
    }

    /**
     * @param Quote $quote
     * @return int|mixed
     * @throws LocalizedException
     */
    protected function getCurrentPointBalance(Quote $quote)
    {
        $currentPointBalance = 0;
        $hasPointForTrial = ($quote->getData('point_for_trial') > 0 )?true:false;
        if (!$hasPointForTrial) {
            $hasPoint = false;
            if ($quote instanceof CartEmulator) {
                try {
                    $customer = $this->_customerRepository->getById($quote->getCustomerId());
                } catch (NoSuchEntityException $e) {
                    return 0;
                }

                $customerRewardPoint = $customer->getCustomAttribute('reward_point');
                if ($customerRewardPoint) {
                    $hasPoint = true;

                    $currentPointBalance = $customerRewardPoint->getValue();
                }
            }
            if (!$hasPoint) {
                $customerCode = $this->getCustomerCode($quote);

                if ($this->_coreRegistry->registry('order_retry-' . $customerCode)) {
                    $statisticData = $this->_shoppingPoint->getPointRealTime($customerCode);
                } else {
                    $statisticData = $this->_shoppingPoint->getPoint($customerCode);
                }

                if (!$statisticData['error']) {
                    $currentPointBalance = isset($statisticData['return']['REST_POINT'])?
                        $statisticData['return']['REST_POINT'] : 0;
                } elseif (isset($statisticData['responseCode'])
                    && $statisticData['responseCode'] != ShoppingPoint::CODE_NOT_FOUND
                ) {
                    $this->logger->critical(__(
                        'Get current point balance error, customer ID #%1, message: %2',
                        $quote->getCustomerId(),
                        $statisticData['msg']
                    ));
                }
            }
        } else {
            $currentPointBalance = $quote->getData('point_for_trial');
        }

        //set current balance points
        $quote->setRewardPointsBalance($currentPointBalance);

        return $currentPointBalance;
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
        if ($total->getUsedPointAmount() ||
            $this->_appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE
        ) {
            return [
                'code' => $this->getCode(),
                'title' => __('Used Point'),
                'value' => $total->getUsedPointAmount(),
                'area'  =>  'footer'
            ];
        }
        return null;
    }

    /**
     * @param Quote $quote
     * @return string|boolean
     */
    private function getCustomerCode($quote)
    {
        try {
            $customer = $this->_customerRepository->getById($quote->getCustomerId());
        } catch (NoSuchEntityException $e) {
            return false;
        }

        $attribute = $customer->getCustomAttribute('consumer_db_id');
        if ($attribute) {
            return $attribute->getValue();
        }
        return false;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    private function _getBaseCodeFee($quote)
    {
        $result = 0.0000;
        if (!$payment = $quote->getPayment()) {
            return $result;
        }
        if ($payment->getMethod() == \Magento\OfflinePayments\Model\Cashondelivery::PAYMENT_METHOD_CASHONDELIVERY_CODE) {
            return $quote->getBaseFee();
        }
        return $result;
    }

    /**
     * Detect this cart is subscription or not
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @return array
     */
    private function _isSubscriptionQuote($quote)
    {
        if (!$quote instanceof \Magento\Framework\DataObject) {
            return false;
        }
        return (bool) $quote->getRikiCourseId();
    }

    /**
     * Revert payment method when change point option
     *
     * @param     $quote
     * @param     $total
     * @param int $noPoint
     * @throws LocalizedException
     */
    private function _resetFee($quote, $total, $noPoint = 0)
    {
        if ($quote instanceof \Riki\Subscription\Model\Emulator\Cart) {
            return;
        }
        $methodDefault = $this->_dataPointHelper->getPreferredMethod();
        $hasPaymentDefault = false;

        if ($methodDefault) {
            $hasPaymentDefault = true;
        }

        if ($quote->getPayment()->getMethod() == 'free') {
            $fee = null;

            if ($this->_appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
                if ($this->_quoteBackendSession->getFreeSurcharge()) {
                    $fee = 0;
                }
            }

            if (!$quote->getFreeSurchargeFee() && is_null($fee) && $hasPaymentDefault) {
                $fee = $this->_helperData->getPaymentCharge($methodDefault);
                $quote->getPayment()->setMethod($methodDefault);
            } else {
                $quote->getPayment()->setMethod('');
                $fee = 0;
            }
            $total->setFee($fee);
            $total->setBaseFee($fee);

            $quote->setFee($fee);
            $quote->setBaseFee($fee);

            $total->setTotalAmount('fee', $fee);
            $total->setBaseTotalAmount('base_fee', $fee);
            if ($noPoint) {
                $total->setGrandTotal($total->getGrandTotal() + $fee);
                $total->setBaseGrandTotal($total->getBaseGrandTotal() + $fee);
            }
        }
    }
}
