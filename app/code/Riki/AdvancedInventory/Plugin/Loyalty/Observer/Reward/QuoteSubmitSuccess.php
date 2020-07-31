<?php
namespace Riki\AdvancedInventory\Plugin\Loyalty\Observer\Reward;

use \Riki\Loyalty\Model\Reward;

class QuoteSubmitSuccess
{
    /** @var \Riki\Loyalty\Model\RewardFactory  */
    protected $rewardFactory;

    /** @var \Riki\Loyalty\Helper\Data  */
    protected $loyaltyHelper;

    /**
     * QuoteSubmitSuccess constructor.
     * @param \Riki\Loyalty\Model\RewardFactory $rewardFactory
     * @param \Riki\Loyalty\Helper\Data $loyaltyHelper
     */
    public function __construct(
        \Riki\Loyalty\Model\RewardFactory $rewardFactory,
        \Riki\Loyalty\Helper\Data $loyaltyHelper
    )
    {
        $this->rewardFactory = $rewardFactory;
        $this->loyaltyHelper = $loyaltyHelper;
    }

    /**
     * Process earned point by promotion for OOS order
     *
     * @param \Riki\Loyalty\Observer\Reward\QuoteSubmitSuccess $subject
     * @param $result
     * @return mixed
     */
    public function afterExecute(
        \Riki\Loyalty\Observer\Reward\QuoteSubmitSuccess $subject,
        $result
    )
    {
        $order = $subject->getOrder();
        $quote = $subject->getQuote();

        if ($quote->getData(\Riki\AdvancedInventory\Model\OutOfStock::OOS_FLAG)) {

            $attribute = $quote->getCustomer()->getCustomAttribute('consumer_db_id');
            if (!$attribute) {
                return $result; // do not process without consumerDb ID
            }
            $customerCode = $attribute->getValue();

            foreach ($order->getAllItems() as $item) {
                $additionalData = $item->getAdditionalData();

                try {
                    $additionalData = \Zend_Json::decode($additionalData);
                } catch (\Exception $e) {
                    return $result;
                }

                if (isset($additionalData['earn_rule_point'])) {
                    foreach ($additionalData['earn_rule_point'] as $ruleId  =>  $earnedPointData) {

                        $expiryPeriod = $earnedPointData['point_expiration_period'];
                        if (!$expiryPeriod) {
                            $expiryPeriod = $this->loyaltyHelper->getDefaultExpiryPeriod();
                        }

                        /** @var \Riki\Loyalty\Model\Reward $tentativeModel */
                        $tentativeModel = $this->rewardFactory->create();
                        $insertData = [
                            'website_id' => $order->getStore()->getWebsiteId(),
                            'sku' => $item->getSku(),
                            'wbs_code' => $earnedPointData['wbs_shopping_point'],
                            'account_code' => $earnedPointData['account_code'],
                            'point' => $earnedPointData['point'],
                            'qty' => $item->getQtyOrdered(),
                            'description' => __('Issued from %1', $ruleId),
                            'sales_rule_id' =>  $ruleId,
                            'point_type' => Reward::TYPE_CAMPAIGN,
                            'order_no' => $order->getIncrementId(),
                            'order_item_id' => $item->getId(),
                            'customer_id' => $order->getCustomerId(),
                            'customer_code' => $customerCode,
                            'status' => Reward::STATUS_TENTATIVE,
                            'action_date' => $this->loyaltyHelper->pointActionDate(),
                            'expiry_period' => $expiryPeriod,
                            'level' => Reward::LEVEL_ITEM
                        ];

                        try {
                            $tentativeModel->addData($insertData)->save();
                        } catch (\Exception $e) {
                            $subject->getLogger()->critical($e);
                        }
                    }
                }
            }
        }

        return $result;
    }
}