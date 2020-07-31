<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit;

use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * Adminhtml customer enquiry search order create abstract block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class SearchOrder extends \Magento\Backend\Block\Widget implements  RendererInterface
{

    /**
     * @var int
     */
    protected $_enquiryid;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var string
     */
    protected $_template = 'enquiryheader/edit/searchorder.phtml';

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     *
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->coreRegistry = $registry;
        $enquiryBlockId = str_replace("searchorder_gridorder_","",$this->coreRegistry->registry('enquiry_block_id'));

        $this->_enquiryid  =  isset($data['enquiry_id'])?$data['enquiry_id']:0;
        if($enquiryBlockId){
            $this->_enquiryid = $enquiryBlockId;
        }

        $this->setId('enquiryheader_search_order_'.$this->_enquiryid);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $oSearchOrderGrid = $this->getLayout()->createBlock('Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit\SearchOrder\GridOrder','',['data' => ['enquiry_id' => $this->_enquiryid]]);
        $this->setChild('enquiryheader_search_order_grid_'.$this->_enquiryid,$oSearchOrderGrid);
        return $this;
    }

    /**
     * Render
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element){
        return $this->toHtml();
    }

    /**
     * Get Header Text
     */
    public function getHeaderText(){
        return __('Please select order number by clicking on a row');
    }
}
