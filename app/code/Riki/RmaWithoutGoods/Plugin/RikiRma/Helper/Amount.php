<?php
namespace Riki\RmaWithoutGoods\Plugin\RikiRma\Helper;

use \Magento\Rma\Model\Rma as RmaModel;

class Amount
{
    /**
     * @param \Riki\Rma\Helper\Amount $subject
     * @param \Closure $proceed
     * @param RmaModel $rma
     * @return mixed
     */
    public function aroundGetEarnedPoint(
        \Riki\Rma\Helper\Amount $subject,
        \Closure $proceed,
        RmaModel $rma
    ) {
        if($rma->getIsWithoutGoods())
            return 0;

        return $proceed($rma);
    }

    /**
     * @param \Riki\Rma\Helper\Amount $subject
     * @param \Closure $proceed
     * @param RmaModel $rma
     * @param $static
     * @return mixed
     */
    public function aroundGetRetractablePoints(
        \Riki\Rma\Helper\Amount $subject,
        \Closure $proceed,
        RmaModel $rma,
        $static = true
    )
    {
        if ($rma->getIsWithoutGoods()) {
            return 0;
        }

        return $proceed($rma, $static);
    }

    /**
     * @param \Riki\Rma\Helper\Amount $subject
     * @param \Closure $proceed
     * @param RmaModel $rma
     * @return mixed
     */
    public function aroundGetPointsToReturn(
        \Riki\Rma\Helper\Amount $subject,
        \Closure $proceed,
        RmaModel $rma
    ) {
        if($rma->getIsWithoutGoods())
            return 0;

        return $proceed($rma);
    }

    /**
     * @param \Riki\Rma\Helper\Amount $subject
     * @param \Closure $proceed
     * @param RmaModel $rma
     * @return mixed
     */
    public function aroundGetReturnShippingAmount(
        \Riki\Rma\Helper\Amount $subject,
        \Closure $proceed,
        RmaModel $rma
    ) {
        if($rma->getIsWithoutGoods())
            return 0;

        return $proceed($rma);
    }

    /**
     * @param \Riki\Rma\Helper\Amount $subject
     * @param \Closure $proceed
     * @param RmaModel $rma
     * @return mixed
     */
    public function aroundGetReturnPaymentFee(
        \Riki\Rma\Helper\Amount $subject,
        \Closure $proceed,
        RmaModel $rma
    ) {
        if($rma->getIsWithoutGoods())
            return 0;

        return $proceed($rma);
    }

    /**
     * @param \Riki\Rma\Helper\Amount $subject
     * @param \Closure $proceed
     * @param RmaModel $rma
     * @return mixed
     */
    public function aroundGetReturnAmount(
        \Riki\Rma\Helper\Amount $subject,
        \Closure $proceed,
        RmaModel $rma
    ) {
        if($rma->getIsWithoutGoods())
            return 0;

        return $proceed($rma);
    }

    /**
     * @param \Riki\Rma\Helper\Amount $subject
     * @param \Closure $proceed
     * @param RmaModel $rma
     * @param $static
     *
     * @return mixed
     */
    public function aroundGetNotRetractablePoints(
        \Riki\Rma\Helper\Amount $subject,
        \Closure $proceed,
        RmaModel $rma,
        $static = true
    )
    {
        if ($rma->getIsWithoutGoods()) {
            return 0;
        }

        return $proceed($rma, $static);
    }

    /**
     * @param \Riki\Rma\Helper\Amount $subject
     * @param \Closure $proceed
     * @param RmaModel $rma
     * @return bool
     */
    public function aroundIsAllowedRefund(
        \Riki\Rma\Helper\Amount $subject,
        \Closure $proceed,
        RmaModel $rma
    ) {
        if($rma->getIsWithoutGoods())
            return true;

        return $proceed($rma);
    }

}
