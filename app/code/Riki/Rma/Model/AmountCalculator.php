<?php
namespace Riki\Rma\Model;

use Magento\Framework\Exception\LocalizedException;

class AmountCalculator
{
    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $rikiAmountHelper;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $rikiDataHelper;

    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $rewardManagement;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    protected $goodsAmount = [];

    /**
     * AmountCalculator constructor.
     * @param \Riki\Rma\Helper\Amount $rikiAmountHelper
     * @param \Riki\Loyalty\Model\RewardManagement $rewardManagement
     */
    public function __construct(
        \Riki\Rma\Helper\Amount $rikiAmountHelper,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache
    ) {
    
        $this->rikiAmountHelper = $rikiAmountHelper;
        $this->rikiDataHelper = $rikiAmountHelper->getDataHelper();
        $this->rewardManagement = $rewardManagement;
        $this->functionCache = $functionCache;
    }

    /**
     * @return \Riki\Framework\Helper\Cache\FunctionCache
     */
    public function getFunctionCache()
    {
        return $this->functionCache;
    }

    /**
     * @return \Riki\Rma\Helper\Data
     */
    public function getDataHelper()
    {
        return $this->rikiDataHelper;
    }

    /**
     * @return \Riki\Rma\Helper\Amount
     */
    public function getAmountHelper()
    {
        return $this->rikiAmountHelper;
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return array
     * @throws LocalizedException
     */
    public function calculateReturnAmount(\Magento\Rma\Model\Rma $rma)
    {
        $returnShippingFeeAdj = floatval($rma->getData('return_shipping_fee_adj'));
        $returnPaymentFeeAdj = floatval($rma->getData('return_payment_fee_adj'));

        $returnShippingFee = $this->rikiAmountHelper->getReturnShippingAmount($rma);
        $returnShippingFeeAdjusted = $returnShippingFee + $returnShippingFeeAdj;

        $returnPaymentFee = $this->rikiAmountHelper->getReturnPaymentFee($rma);
        $returnPaymentFeeAdjusted = $returnPaymentFee + $returnPaymentFeeAdj;

        /** Total before point adjustment - Points to return */
        $pointToReturn = $this->calPointsToReturn($rma, $returnShippingFeeAdjusted, $returnPaymentFeeAdjusted);

        /** Total before point adjustment - Total before point adjustment */
        $totalBeforePointAdjustment = $this->calGoodsAmountAdjusted($rma) +
            $returnShippingFeeAdjusted +
            $returnPaymentFeeAdjusted -
            $pointToReturn;

        if ($rma->getIsWithoutGoods()) {
            $totalReturnAmount = null;

            $totalReturnAmountAdjusted = $totalBeforePointAdjustment
                + floatval($rma->getData('refund_without_product'))
                - floatval($rma->getData('total_return_point_adj'))
                + floatval($rma->getData('total_return_amount_adj'));
        } else {
            if ($this->rikiDataHelper->isCodAndNpAtobaraiShipmentRejected($rma)) {
                /** Total to Return / Refund - Total before global adjustment (only available for normal return) */
                $totalReturnAmount = $totalBeforePointAdjustment;
            } else {
                $totalReturnAmount = max(0, $totalBeforePointAdjustment - $this->rikiAmountHelper->getNotRetractablePoints($rma));
            }

            /** Total to Return / Refund - Final return / Refund amount */
            $totalReturnAmountAdjusted = $totalReturnAmount
                + floatval($rma->getData('total_return_amount_adj'));
        }

        if ($this->rikiDataHelper->isCodAndNpAtobaraiShipmentRejected($rma)) {
            /** Points to return - Remain of not retractable points */
            $remainNotRetractablePoints = 0;
        } else {
            $remainNotRetractablePoints = max(0, $this->rikiAmountHelper->getNotRetractablePoints($rma) - $totalBeforePointAdjustment);
        }

        /** Points to return - Points to return before adjustment*/
        $pointToReturnBeforeAdjustment = $pointToReturn - $remainNotRetractablePoints;

        return [
                'customer_point_balance'    =>  $this->rikiAmountHelper->getPointsBalance($rma),
                'returnable_point_amount'    =>  $pointToReturn,
                'return_shipping_fee'   =>  $returnShippingFee,
                'return_shipping_fee_adjusted'   =>  $returnShippingFeeAdjusted,
                'return_payment_fee'   =>  $returnPaymentFee,
                'return_payment_fee_adjusted'   =>  $returnPaymentFeeAdjusted,
                'total_cancel_point'   =>  $this->rikiAmountHelper->getPointsToCancel($rma),
                'total_cancel_point_adjusted'   =>  $this->rikiAmountHelper->getPointsToCancel($rma) + floatval($rma->getData('total_cancel_point_adj')),
                'earned_point'   =>  $this->rikiAmountHelper->getEarnedPoint($rma),
                'total_return_amount'   =>  $totalReturnAmount,
                'total_return_amount_adjusted'   =>  $totalReturnAmountAdjusted,
                'total_return_point'   =>  $pointToReturnBeforeAdjustment,
                'total_return_point_adjusted'   =>  $pointToReturnBeforeAdjustment + floatval($rma->getData('total_return_point_adj')),
            ];
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @param $returnShippingFeeFinal
     * @param $returnPaymentFeeFinal
     * @return int|mixed
     */
    public function calPointsToReturn(\Magento\Rma\Model\Rma $rma, $returnShippingFeeFinal, $returnPaymentFeeFinal)
    {
        if ($rma->getIsWithoutGoods() || $this->rikiAmountHelper->isFreeReturn($rma)) {
            return 0;
        } else {
            $goodAmount = $this->calGoodsAmountAdjusted($rma);

            $returnablePoints = $this->getReturnablePointAmount($rma);

            if ($this->rikiDataHelper->isCodAndNpAtobaraiShipmentRejected($rma)) {
                $shoppingPoint = min(
                    $this->rikiAmountHelper->getShipment($rma)->getShoppingPointAmount(),
                    $returnablePoints
                );
            } else {
                $order = $this->rikiDataHelper->getRmaOrder($rma);
                if (!$order instanceof \Magento\Sales\Model\Order) {
                    return 0;
                }

                $shoppingPoint = $returnablePoints;
            }

            return min($goodAmount + $returnShippingFeeFinal + $returnPaymentFeeFinal, $shoppingPoint);
        }
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return int|mixed
     */
    public function getReturnablePointAmount(\Magento\Rma\Model\Rma $rma)
    {
        $order = $this->rikiDataHelper->getRmaOrder($rma);
        if (!$order instanceof \Magento\Sales\Model\Order) {
            return 0;
        }

        $returnedPointAmount = $this->rewardManagement->convertPointToAmount($this->rikiAmountHelper->getReturnedPoint($rma));
        $capturedPointAmount = $this->rewardManagement->convertPointToAmount($this->rikiAmountHelper->getCapturedPoint($rma));

        return max(0, intval($order->getUsedPointAmount() - ($returnedPointAmount + $capturedPointAmount)));
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return float|int
     */
    public function calGoodsAmountAdjusted(\Magento\Rma\Model\Rma $rma)
    {
        $rmaId = $rma->getId();

        if (!isset($this->goodsAmount[$rmaId])) {
            $goodAmount = $this->rikiAmountHelper->getReturnedGoodsAmount($rma);

            foreach ($rma->getItemsForDisplay() as $item) {
                $goodAmount += floatval($item->getData('return_amount_adj')) + floatval($item->getData('return_wrapping_fee_adj'));
            }

            $this->goodsAmount[$rmaId] = $goodAmount;
        }

        return $this->goodsAmount[$rmaId];
    }
}
