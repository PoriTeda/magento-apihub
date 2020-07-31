<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit\SearchOrder;

/**
 * Adminhtml enquiry create search order block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class GridOrder extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    /**
     * @var
     */
    protected $_enquiryid;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * @var \Magento\Sales\Model\Config
     */
    protected $_orderStatus;
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Sales\Model\Order\OrderFactory
     * @param \Riki\Customer\Helper\Config
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Sales\Model\OrderFactory $salesOrderCollectionFactory,
        \Riki\Customer\Helper\Config $configHelper,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_orderFactory = $salesOrderCollectionFactory;
        $this->_configHelper = $configHelper;

        $this->coreRegistry = $registry;
        $enquiryBlockId = str_replace("searchorder_gridorder_","",$this->coreRegistry->registry('enquiry_block_id'));
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
        $this->setId('enquiryheader_search_order_grid_'.$this->_enquiryid);
        $this->setRowClickCallback('function (grid, row){
         var trElement = Event.findElement(event, \'tr\');
         var orderIdElement = trElement.select(\'td\')[0].innerHTML.trim();
         var customerIdElement = trElement.select(\'td\')[7].innerHTML.trim();
         var consumerNameElement      = trElement.select(\'td\')[9].innerHTML.trim() + " " + trElement.select(\'td\')[8].innerHTML.trim();

          $(\'enquiryheader_'.$this->_enquiryid.'_order_id\').value = orderIdElement;

         if(isNaN(customerIdElement) == false){

             $(\'enquiryheader_'.$this->_enquiryid.'_customer_id\').value = customerIdElement;
             $(\'enquiryheader_'.$this->_enquiryid.'_consumer_name\').value = consumerNameElement;
         }
         $(searchorder).hide();
         $$(\'.action-searchorder span\')[0].innerHTML = "'.__('Search Order Number').'";
        }');
        $this->setDefaultSort('increment_id');
        $this->setDefaultDir('DESC');
        $this->setUseAjax(true);
    }

    /**
     * Prepare collection to be displayed in the gridml
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->_orderFactory->create()->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Add column filtering conditions to collection
     *
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($this->getCollection()) {

            $field = $column->getFilterIndex() ? $column->getFilterIndex() : $column->getIndex();

            $condition = $column->getFilter()->getCondition();
            if ($field && isset($condition)) {
                if(strpos((string)$this->getCollection()->getSelect(),'sog') === false){
                    $this->getCollection()->getSelect()->joinInner(
                        ['sog' => 'sales_order_grid'],
                        'main_table.entity_id= sog.entity_id',
                        []
                    );
                }
                $fieldsMapper = ['increment_id','created_at','grand_total','status'];
                if(in_array($field,$fieldsMapper)){
                    $this->getCollection()->addFilterToMap(
                        $field,
                        'main_table.'.$field
                    );
                }
                $this->getCollection()->addFieldToFilter($field, $condition);
            }
        }
        return $this;
    }

    /**
     * Prepare columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn('increment_id', ['header' => __('Order ID'), 'index' => 'increment_id']);

        $this->addColumn(
            'created_datetime',
            [
                'header' => __('Purchased Date'),
                'index' => 'created_at',
                'type' => 'datetime'
            ]
        );

        $this->addColumn('billing_name', [
                'header' => __('Bill-to Name'),
                'index' => 'billing_name',
                'renderer' => 'Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit\SearchOrder\GridOrder\Column\Render\BillingName'
            ]
        );

        $this->addColumn('shipping_name', [
                'header' => __('Ship-to Name'),
                'index' => 'shipping_name',
                'renderer' => 'Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit\SearchOrder\GridOrder\Column\Render\ShippingName'
            ]
        );

        $this->addColumn('grand_total', [
                'header' => __('Grand Total (Purchased)'),
                'index' => 'grand_total',
                'renderer' => 'Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit\SearchOrder\GridOrder\Column\Render\GrandTotal'
            ]
        );

        $this->addColumn('status', [
                'header' => __('Status'),
                'index' => 'status',
                'type' =>'options',
                'options'=> $this->_configHelper->getOptionOrderStatusArray()
            ]
        );

        $this->addColumn('payment_method', [
                'header' => __('Payment Method'),
                'index' => 'payment_method',
                'type' =>'options',
                'renderer' => 'Riki\Customer\Block\Adminhtml\EnquiryHeader\Edit\SearchOrder\GridOrder\Column\Render\PaymentMethod',
                'options'=> $this->_configHelper->getOrderPaymentMethodArray()
            ]
        );

        $this->addColumn('customer_id', ['header' => __('Customer ID'), 'index' => 'customer_id']);
        $this->addColumn('customer_firstname', ['header' => __('First Name'), 'index' => 'customer_firstname']);
        $this->addColumn('customer_lastname', ['header' => __('Last Name'), 'index' => 'customer_lastname']);

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
            ['block' => 'searchorder_gridorder_'.$this->_enquiryid, '_current' => true, 'collapse' => null]
        );
    }

}
