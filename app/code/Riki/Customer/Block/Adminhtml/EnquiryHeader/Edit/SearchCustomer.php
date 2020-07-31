<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit;

use Magento\Framework\Data\Form\Element\Renderer\RendererInterface;

/**
 * Adminhtml customer enquiry search customer create abstract block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.NumberOfChildren)
 */
class SearchCustomer extends \Magento\Backend\Block\Widget implements  RendererInterface
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
    protected $_template = 'enquiryheader/edit/searchcustomer.phtml';

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
        $enquiryBlockId = str_replace("searchcustomer_gridcustomer_","",$this->coreRegistry->registry('enquiry_block_id'));

        $this->_enquiryid  =  isset($data['enquiry_id'])?$data['enquiry_id']:0;
        if($enquiryBlockId){
            $this->_enquiryid = $enquiryBlockId;
        }

        $this->setId('enquiryheader_search_customer_'.$this->_enquiryid);
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        $oSearchCustomerGrid = $this->getLayout()->createBlock('Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit\SearchCustomer\GridCustomer','',['data' => ['enquiry_id' => $this->_enquiryid]]);
        $this->setChild('enquiryheader_search_customer_grid_'.$this->_enquiryid,$oSearchCustomerGrid);
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
        return __('Please select customer id by clicking on a row');
    }
}
