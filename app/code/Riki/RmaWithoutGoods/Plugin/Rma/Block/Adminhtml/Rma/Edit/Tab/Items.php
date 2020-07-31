<?php
namespace Riki\RmaWithoutGoods\Plugin\Rma\Block\Adminhtml\Rma\Edit\Tab;

class Items
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    public function __construct(
        \Magento\Framework\Registry $registry
    ){
        $this->_coreRegistry = $registry;
    }

    /**
     * @param  \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items $subject
     * @param \Closure $proceed
     * @return string
     */
    public function aroundGetHeaderText(
        \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items $subject,
        \Closure $proceed
    ) {

        $rma = $this->_coreRegistry->registry('current_rma');

        if($rma && $rma->getIsWithoutGoods()){
            return '';
        }

        return $proceed();
    }
}
