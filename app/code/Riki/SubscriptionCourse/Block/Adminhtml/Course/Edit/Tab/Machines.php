<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab;

class Machines extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $_courseFactory;

    /**
     * Products constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $course
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Registry $coreRegistry,
        \Riki\SubscriptionCourse\Model\CourseFactory $course,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        array $data = []
    ) {
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_productFactory = $productFactory;
        $this->_courseFactory = $course;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * _construct
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('machinesGrid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
        if ($this->getRequest()->getParam('course_id')) {
            $this->setDefaultFilter(array('in_products' => 1));
        }
    }

    public function getSubscriptionCourseMachine()
    {
        $courseId = $this->getRequest()->getParam('course_id');
        $courseModel   = $this->_courseFactory->create();
        if ($courseId) {
            return $courseModel->getMachinesByCourse($courseId);
        }
        return false;
    }

    /**
     * add Column Filter To Collection
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() == 'in_products') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $productIds]);
            } else {
                if ($productIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', ['nin' => $productIds]);
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }
    /**
     * prepare collection
     */
    protected function _prepareCollection()
    {
        $collection = $this->_productCollectionFactory->create();
        $collection->addAttributeToSelect('*');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_products',
            [
                'type' => 'checkbox',
                'name' => 'in_products',
                'values' => $this->_getSelectedProducts(),
                'align' => 'center',
                'index' => 'entity_id',
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select'
            ]
        );

        $this->addColumn(
            'name',
            [
                'header' => __('Product Name'),
                'index' => 'name',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'index' => 'sku',
                'header_css_class' => 'col-sku',
                'column_css_class' => 'col-sku'
            ]
        );

        $this->addColumn(
            'is_free',
            [
                'header' => __('Free or Discount'),
                'index' => 'is_free',
                'name' => 'is_free',
                'type' => 'options',
                'editable' => true,
                'edit_only' => false,
                'options' => [1 => __('Free'), 0 => __('Discount')],
                'renderer' => 'Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Products\Column\FreeOrDiscount'
            ]
        );

        $this->addColumn(
            'discount_amount',
            [
                'header' => __('Discount %'),
                'index' => 'discount_amount',
                'header_css_class' => 'col-discount-amount',
                'column_css_class' => 'col-discount-amount',
                'editable' => true,
                'edit_only' => false,
                'type' =>'input',
                'renderer' => 'Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Products\Column\DiscountAmount'
            ]
        );

        $this->addColumn(
            'wbs',
            [
                'header' => __('WBS'),
                'index' => 'wbs',
                'header_css_class' => 'col-wbs',
                'column_css_class' => 'col-wbs',
                'editable' => true,
                'edit_only' => false,
                'type' =>'input',
                'renderer' => 'Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Products\Column\Wbs'
            ]
        );

        $this->addColumn(
            'sort_order',
            [
                'header' => __('Display order'),
                'type' => 'input',
                'validate_class' => 'validate-number',
                'index' => 'sort_order',
                'editable' => true,
                'edit_only' => false,
                'header_css_class' => 'col-position',
                'column_css_class' => 'col-position',
                'renderer' => 'Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Products\Column\SortOrder'
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Retrieve grid reload url
     *
     * @return string;
     */
    public function getGridUrl() {
        return $this->getUrl('*/*/machinesgrid', ['_current' => true]);
    }

    public function getRowUrl($row) {
        return '';
    }

    public function canShowTab() {
        return false;
    }

    public function isHidden() {
        return true;
    }

    public function _getSelectedProducts() {
        return $products = array_keys($this->getSelectedProducts());
    }

    public function getSelectedProducts() {
        $machines = $this->getSubscriptionCourseMachine();
        $productIds = [];

        if ($machines) {
            foreach ($machines as $machine) {
                $productIds[$machine['product_id']] = ['position' => $machine['sort_order']];
            }
        }

        return $productIds;
    }
}