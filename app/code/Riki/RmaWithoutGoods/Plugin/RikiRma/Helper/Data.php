<?php
namespace Riki\RmaWithoutGoods\Plugin\RikiRma\Helper;

use \Magento\Rma\Model\Rma as RmaModel;

class Data
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    protected $_request;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\RequestInterface $request
    ){
        $this->_coreRegistry = $registry;
        $this->_request = $request;
    }

    /**
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundCanShowPartialFullField(
        \Riki\Rma\Helper\Data $subject,
        \Closure $proceed
    ) {
        $rma = $this->_coreRegistry->registry('current_rma');

        if($rma && $rma->getIsWithoutGoods()){
            return false;
        }

        if(
            $this->_request->getModuleName() == 'rma_wg'
            && $this->_request->getActionName() == 'newAction'
        ){
            return false;
        }

        return $proceed();
    }

    /**
     * @param \Closure $proceed
     * @return mixed
     */
    public function aroundCanShowReturnedWarehouseField(
        \Riki\Rma\Helper\Data $subject,
        \Closure $proceed
    ) {
        $rma = $this->_coreRegistry->registry('current_rma');

        if($rma && $rma->getIsWithoutGoods()){
            return false;
        }

        if(
            $this->_request->getModuleName() == 'rma_wg'
            && $this->_request->getActionName() == 'newAction'
        ){
            return false;
        }

        return $proceed();
    }
}
