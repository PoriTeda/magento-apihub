<?php
namespace Riki\RmaWithoutGoods\Plugin\RikiRma\Block\Adminhtml\Rma\Edit\Tab\Items;

class Warning
{
    /**
     * do not show warning message for return without goods
     *
     * @param \Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Warning $subject
     * @param \Closure $proceed
     * @return array
     */
    public function aroundGetMessages(
        \Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items\Warning $subject,
        \Closure $proceed
    ) {

        $rma = $subject->getRma();

        if($rma->getIsWithoutGoods()){
            return [];
        }

        return $proceed($rma);
    }
}
