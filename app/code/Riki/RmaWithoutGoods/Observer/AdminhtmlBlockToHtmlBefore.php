<?php
namespace Riki\RmaWithoutGoods\Observer;

use Magento\Framework\Event\ObserverInterface;

class AdminhtmlBlockToHtmlBefore implements ObserverInterface
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
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $block = $observer->getBlock();

        if($block instanceof \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\Items){
            $rma = $this->_coreRegistry->registry('current_rma');

            if($rma && $rma->getIsWithoutGoods()){
                $block->unsetChild('items_grid');
            }
        }
    }
}