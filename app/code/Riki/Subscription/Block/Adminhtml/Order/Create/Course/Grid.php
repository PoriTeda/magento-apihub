<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Block\Adminhtml\Order\Create\Course;

/**
 * Adminhtml sales order create search products block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /**
     * Session quote
     *
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuote;

    /**
     * Product factory
     *
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;
    protected $_course;

    protected $_subscriptionCourseResourceModel;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Riki\SubscriptionCourse\Model\ResourceModel\Course $subscriptionCourseResourceModel
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $subscriptionCourseResourceModel,
        \Magento\Backend\Model\Session\Quote $sessionQuote

    ) {
        $this->_productFactory = $productFactory;
        $this->_catalogConfig = $catalogConfig;
        $this->_sessionQuote = $sessionQuote;
        $this->_subscriptionCourseResourceModel = $subscriptionCourseResourceModel;
        parent::__construct($context, $backendHelper);
    }

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('sales_order_create_course_grid');
        $this->setDefaultSort('course_id');
        $this->setUseAjax(true);

        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
    }

    /**
     * Prepare collection to be displayed in the grid
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $request = $this->_sessionQuote->getData();
        $customerId = isset($request['customer_id'])?$request['customer_id']:null;
        $storeId = isset($request['store_id'])?$request['store_id']:null;
        if ($customerId == null){
            return $this;
        }
        /* @var $collection \Riki\SubscriptionCourse\Model\ResourceModel\Course\Collection */
        $collection = $this->_subscriptionCourseResourceModel->getCoursesByMembership($customerId,$storeId);
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }


    public function getRowClickCallback()
    {
        $script = <<<JQUERY
    function (grid, event) {            
             return order.loadProductCourse(grid,event);
            }
JQUERY;
        return $script;
    }

    /**
     * Prepare columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'course_id',
            [
                'header' => __('ID'),
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'index' => 'course_id',
                'sortable'  => true,
            ]
        );

        $this->addColumn(
            'course_code',
            [
                'header' => __('Course Code'),
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id',
                'index' => 'course_code',
                'sortable'  => true,
            ]
        );

        $this->addColumn(
            'course_name',
            [
                'header' => __('Course Name'),
                'index' => 'course_name'
            ]
        );

        $this->addColumn(
            'description',
            [
                'header' => __('Description'),
                'index' => 'description'
            ]
        );
        $this->addColumn(
            'launch_date',
            [
                'header' => __('Launch Date'),
                'index' => 'launch_date',
                'type' => 'date',
                'timezone' => true,
                'filter' => 'Riki\Subscription\Block\Widget\Grid\Column\Filter\Date',
                'header_css_class' => 'custom-width-has-calendar'
            ]
        );
        $this->addColumn(
            'close_date',
            [
                'header' => __('Close Date'),
                'index' => 'close_date',
                'type' => 'date',
                'timezone' => true,
                'filter' => 'Riki\Subscription\Block\Widget\Grid\Column\Filter\Date',
                'header_css_class' => 'custom-width-has-calendar'
            ]
        );
        $this->addColumn(
            'minimum_order_qty',
            [
                'header' => __('Minimum Order Quantity'),
                'index' => 'minimum_order_qty'
            ]
        );
        $this->addColumn(
            'minimum_order_times',
            [
                'header' => __('Minimum Order Time'),
                'index' => 'minimum_order_times'
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
            'sales/*/loadBlock',
            ['block' => 'course_grid', '_current' => true, 'collapse' => null]
        );
    }

    public function getRowUrl($row) {
        return $this->getUrl('profile/product/index', array('id' => $row->getId()));
    }
}
