<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Block\Adminhtml\Customer\Tab\View;

use Magento\Customer\Controller\RegistryConstants;

/**
 * Adminhtml customer recent orders grid block
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * @var \Riki\Subscription\Model\Profile\Profile
     */
    protected $_profileFactory;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
		
        $this->_coreRegistry = $coreRegistry;
        $this->_profileFactory = $profileFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Initialize the orders grid.
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('subscription_profile_id');
        $this->setDefaultSort('profile_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        $this->setFilterVisibility(false);
        $this->getResetFilterButtonHtml();

    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareCollection()
    {
        $customerId = $this->getParam('id');
        $collection = $this->_profileFactory->create()->getCollection();
        $collection->getSelect()
                   ->join(
                       array('sc'=>'subscription_course'),
                       'main_table.course_id = sc.course_id',
                       array('course_name')
                   )
                   ->where('main_table.type != "tmp" OR main_table.type IS NULL')
        ;
        $collection->addFieldToFilter('customer_id',$customerId);
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     */
     protected function _prepareColumns()
    {
        $this->addColumn(
            'profile_id',
            [
                'header' => __('ID'),
                'index' => 'profile_id',
                'type' => 'number',
                'width' => '100px',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'course_name',
            [
                'header' => __('Course Name'),
                'index' => 'course_name',
            ]
        );

        $this->addColumn(
            'course_id',
            [
                'filter' => false,
                'header' => __('Course ID'),
                'index' => 'course_id'
            ]
        );

        $this->addColumn(
            'next_delivery_date',
            [
                'header' => __('Next Delivery Date'),
                'index' => 'next_delivery_date',
            ]
        );
        $this->addColumn(
            'status',
            [
                'filter' => false,
                'header' => __('Status'),
                'index' => 'status',
                'type' =>'options',
                'options'=>[1=>__('Ongoing'),2=>__('Completed'),0=>__('Disengaged')]
            ]
        );
        $this->addColumn(
            'ndelivery',
            [
                'filter' => false,
                'header' => __('Next delivery #N date'),
                'renderer' => '\Riki\Subscription\Block\Adminhtml\Customer\Tab\View\Grid\Column\Renderer\NDelivery',
            ]
        );
        $this->addColumn(
            'n1delivery',
            [
                'filter' => false,
                'header' => __('Delivery #N+1 date'),
                'renderer' => '\Riki\Subscription\Block\Adminhtml\Customer\Tab\View\Grid\Column\Renderer\N1Delivery',
            ]
        );
        $this->addColumn(
            'n2delivery',
            [
                'filter' => false,
                'header' => __('Delivery #N+2 date'),
                'renderer' => '\Riki\Subscription\Block\Adminhtml\Customer\Tab\View\Grid\Column\Renderer\N2Delivery',
            ]
        );
        $this->addColumn('action_edit', array(
            'header' => __('Action'),
            'type' => 'action',
            'getter' => 'getId',
            'renderer' => '\Riki\Subscription\Block\Adminhtml\Profile\AddSpotProduct\Grid\Column\Renderer\Link',
            'filter' => false,
            'sortable' => false,
            'index' => 'stores',
            'is_system' => true,
            'header_css_class' => 'col-actions',
            'column_css_class' => 'col-actions'
        ));

        return parent::_prepareColumns();
    }
    /**
     * Get Url to action
     *
     * @param  string $action action Url part
     * @return string
     */
    protected function _getControllerUrl($action = '')
    {
        return '*/*/' . $action;
    }

    /**
     * Get headers visibility
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getHeadersVisibility()
    {
        return $this->getCollection()->getSize() >= 0;
    }

    /**
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('profile/profile/index', array('_current' => true));
    }

    /**
     * {@inheritdoc}
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('profile/profile/edit', ['id' => $row->getProfileId()]);
    }

}
