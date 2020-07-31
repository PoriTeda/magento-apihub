<?php
namespace Riki\RmaWithoutGoods\Plugin\Rma\Block\Adminhtml\Rma\Edit;

class Tabs
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
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit\Tabs $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\DataObject|TabInterface $tab
     * @return string
     */
    public function aroundGetTabTitle(
        \Magento\Rma\Block\Adminhtml\Rma\Edit\Tabs $subject,
        \Closure $proceed,
        $tab
    ) {

        $rma = $this->_coreRegistry->registry('current_rma');

        if($rma && $rma->getIsWithoutGoods() && $tab->getId() == 'items_section'){
            return __('Return Amount');
        }

        return $proceed($tab);
    }

    /**
     * @param \Magento\Rma\Block\Adminhtml\Rma\Edit\Tabs $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\DataObject|TabInterface $tab
     * @return string
     */
    public function aroundGetTabLabel(
        \Magento\Rma\Block\Adminhtml\Rma\Edit\Tabs $subject,
        \Closure $proceed,
        $tab
    ) {

        $rma = $this->_coreRegistry->registry('current_rma');

        if($rma && $rma->getIsWithoutGoods() && $tab->getId() == 'items_section'){
            return __('Return Amount');
        }

        return $proceed($tab);
    }
}
