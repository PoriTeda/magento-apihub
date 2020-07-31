<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit\SearchCustomer;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;

/**
 * Adminhtml enquiry create search customer block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class GridCustomer extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Riki\Customer\Helper\Config
     */
    protected $_configHelper;

    /**
     * GridCustomer constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Riki\Customer\Helper\Config $configHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\Customer\Helper\Config $configHelper,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_customerFactory = $customerFactory;
        $this->_configHelper = $configHelper;

        $this->coreRegistry = $registry;
        $enquiryBlockId = str_replace("searchcustomer_gridcustomer_","",$this->coreRegistry->registry('enquiry_block_id'));

        $this->_enquiryid  =  isset($data['enquiry_id'])?$data['enquiry_id']:0;
        if($enquiryBlockId){
            $this->_enquiryid = $enquiryBlockId;
        }


        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('enquiryheader_search_customer_grid_'.$this->_enquiryid);
        $this->setRowClickCallback('function (grid, row){
         var trElement = Event.findElement(event, \'tr\');
         var customerIdElement      = trElement.select(\'td\')[0].innerHTML.trim();
         var consumerNameElement    = trElement.select(\'td\')[3].innerHTML.trim() + " " + trElement.select(\'td\')[2].innerHTML.trim()

         $(\'enquiryheader_'.$this->_enquiryid.'_customer_id\').value = customerIdElement;
         $(\'enquiryheader_'.$this->_enquiryid.'_consumer_name\').value = consumerNameElement;

         $(searchcustomer).hide();
         $$(\'.action-searchcustomer span\')[0].innerHTML = "'.__('Search Customer ID').'";
        }');
        $this->setDefaultSort('increment_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
    }

    /**
     * Prepare collection to be displayed in the grid
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_customerFactory->create()->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', ['header' => __('ID'), 'index' => 'entity_id']);
        $this->addColumn('email', ['header' => __('Email'), 'index' => 'email']);
        $this->addColumn('firstname', ['header' => __('First Name'), 'index' => 'firstname']);
        $this->addColumn('lastname', ['header' => __('Last Name'), 'index' => 'lastname']);
        $this->addColumn('firstnamekana', [
                                            'header' => __('First Name Kana'),
                                            'index' => 'firstnamekana',
                                            'renderer' => 'Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit\SearchCustomer\GridCustomer\Column\Render\FirstnameKana'
                                          ]
                        );
        $this->addColumn('lastnamekana', [
                                            'header' => __('Last Name Kana'),
                                            'index' => 'lastnamekana',
                                            'renderer' => 'Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit\SearchCustomer\GridCustomer\Column\Render\LastnameKana'
                                         ]
                         );

        $this->addColumn('group_id', [
                                        'header' => __('Group'),
                                        'index' => 'group_id',
                                        'type' =>'options',
                                        'options'=> $this->_configHelper->getOptionCustomerGroupArray()
                                     ]
                        );

        $this->addColumn('website_id',
                            [
                              'header' => __('Website'),
                              'index' => 'website_id',
                              'type' =>'options',
                              'options' => $this->_configHelper->getOptionCustomerWebsiteArray()
                            ]
                        );
        $this->addColumn('gender',
                            [
                             'header' => __('Gender'),
                             'index' => 'gender',
                             'type' =>'options',
                             'options'=>[1=>__('Male'),2=>__('Female'),3=>__('Not Specified')]
                            ]
                        );

        return parent::_prepareColumns();
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            'customer/enquiryheader_edit/LoadBlock',
            ['block' => 'searchcustomer_gridcustomer_'.$this->_enquiryid, '_current' => true, 'collapse' => null]
        );
    }

}
