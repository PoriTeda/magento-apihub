<?php
namespace Riki\RmaWithoutGoods\Plugin\RikiRma\Block\Adminhtml\Rma\Edit\Tab\Items;

class TotalBefore
{
    const WITHOUT_GOODS_RMA = 2;

    /**
     * @param \Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\TotalBeforePointAdjustment $subject
     * @param \Closure $proceed
     * @return int|mixed
     */
    public function aroundGetReturnGoodsType(
        \Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\TotalBeforePointAdjustment $subject,
        \Closure $proceed
    ) {

        $rma = $subject->getRma();

        if($rma->getIsWithoutGoods()){
            return self::WITHOUT_GOODS_RMA;
        }

        return $proceed();
    }
}
