<?php

namespace Riki\Loyalty\Model;

use Magento\Customer\Model\Customer;
use Riki\Loyalty\Model\ConsumerDb\ShoppingPoint;

class Conversion
{
    /**
     * @var Reward
     */
    protected $_rewardModel;

    /**
     * @var ShoppingPoint
     */
    protected $_consumerDb;

    /**
     * @var Customer
     */
    protected $_customerModel;

    /**
     * @var \Riki\Loyalty\Helper\Data
     */
    protected $_loyaltyHelper;

    /**
     * Conversion constructor.
     *
     * @param Reward $reward
     * @param ShoppingPoint $consumerDb
     * @param Customer $customer
     */
    public function __construct(
        Reward $reward,
        ShoppingPoint $consumerDb,
        Customer $customer,
        \Riki\Loyalty\Helper\Data $helper
    )
    {
        $this->_rewardModel = $reward;
        $this->_consumerDb = $consumerDb;
        $this->_customerModel = $customer;
        $this->_loyaltyHelper = $helper;
    }

    /**
     * Do convert to shopping point
     *
     * @param \Magento\Sales\Model\Order $order
     * @param int $status
     * @return void
     */
    public function toShoppingPoint($order, $status)
    {
        $orderNo = $order->getIncrementId();
        $tentativeReward = $this->_rewardModel->getResource()->getPointToConvert($orderNo, $status);
        if (!sizeof($tentativeReward)) {
            return;
        }
        //convert to shopping point
        $customer = $this->_customerModel->load($order->getCustomerId());
        $customerCode = $customer->getData('consumer_db_id');
        foreach ($tentativeReward as $tentativePoint) {
            $wbsCode = ($tentativePoint['wbs_code'] ? $tentativePoint['wbs_code'] : '');
            $accountCode = ($tentativePoint['account_code'] ? $tentativePoint['account_code'] : '');
            $arrData = [
                'pointIssueType' => $tentativePoint['point_type'],
                'description' => $tentativePoint['description'],
                'pointAmountId' => ShoppingPoint::POINT_AMOUNT_ID,
                'point' => $tentativePoint['total_point'],
                'orderNo' => $orderNo,
                'scheduledExpiredDate' => $this->_loyaltyHelper->scheduledExpiredDate($tentativePoint['expiry_period']),
                'serialNo' => '',
                'wbsCode' => $wbsCode,
                'accountCode' => $accountCode,

            ];
            $response = $this->_consumerDb->setPoint(ShoppingPoint::REQUEST_TYPE_ALLOCATION, $customerCode, $arrData);
            $status = $response['error']  ? Reward::STATUS_ERROR : Reward::STATUS_SHOPPING_POINT;
            $this->_rewardModel->getResource()->updateStatusFromIds(explode(',', $tentativePoint['ids']), $status);
        }
        return;
    }
}
