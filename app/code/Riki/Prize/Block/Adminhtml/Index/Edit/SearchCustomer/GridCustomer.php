<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Prize\Block\Adminhtml\Index\Edit\SearchCustomer;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;

/**
 * Adminhtml enquiry create search customer block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class GridCustomer extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * @var \Riki\Customer\Helper\Config
     */
    protected $_configHelper;
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Customer\Model\CustomerFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Riki\Customer\Helper\Config $configHelper,
        array $data = []
    ) {
        $this->_customerFactory = $customerFactory;
        $this->_configHelper = $configHelper;
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
        $this->setId('prize_search_customer_grid');
        $this->setRowClickCallback('function (grid, row){
         var trElement = row.target.parentElement;
         var customerIdElement = trElement.select(\'td\')[0].innerHTML.trim();
         $(\'prize_consumer_db_id\').value = customerIdElement;
         $(prize_searchcustomer).hide();
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
        $collection->addFieldToFilter('consumer_db_id', ['notnull' => true]);
        $collection->addFieldToFilter('consumer_db_id', ['neq' => '']);
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
        $this->addColumn('entity_id', ['header' => __('Consumer Id'), 'index' => 'consumer_db_id']);
        $this->addColumn('email', ['header' => __('Email'), 'index' => 'email']);
        $this->addColumn('firstname', ['header' => __('First Name'), 'index' => 'firstname']);
        $this->addColumn('lastname', ['header' => __('Last Name'), 'index' => 'lastname']);
        $this->addColumn('firstnamekana', [
                                            'header' => __('First Name Kana'),
                                            'index' => 'firstnamekana',
                                            'renderer' => 'Riki\Prize\Block\Adminhtml\Index\Edit\SearchCustomer\GridCustomer\Column\Render\FirstnameKana'
                                          ]
                        );
        $this->addColumn('lastnamekana', [
                                            'header' => __('Last Name Kana'),
                                            'index' => 'lastnamekana',
                                            'renderer' => 'Riki\Prize\Block\Adminhtml\Index\Edit\SearchCustomer\GridCustomer\Column\Render\LastnameKana'
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
            'prize/index_edit/LoadBlock',
            ['block' => 'searchcustomer_gridcustomer', '_current' => true, 'collapse' => null]
        );
    }

}
