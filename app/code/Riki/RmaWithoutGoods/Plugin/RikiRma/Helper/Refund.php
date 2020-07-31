<?php
namespace Riki\RmaWithoutGoods\Plugin\RikiRma\Helper;

use \Magento\Rma\Model\Rma as RmaModel;

class Refund
{

    /** @var \Riki\Rma\Helper\Data  */
    protected $_rmaHelper;

    /**
     * @param \Riki\Rma\Helper\Data $rmaHelper
     */
    public function __construct(
        \Riki\Rma\Helper\Data $rmaHelper
    ){
        $this->_rmaHelper = $rmaHelper;
    }

    /**
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundPrepareRefundItems(
        \Riki\Rma\Helper\Refund $subject,
        \Closure $proceed,
        \Magento\Rma\Model\Rma $rma
    ) {

        if($rma->getIsWithoutGoods()){

            return [0   =>  ['qty'  =>  0]];
        }

        return $proceed($rma);
    }
}
