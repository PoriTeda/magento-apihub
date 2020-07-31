<?php
namespace Riki\Subscription\Block\Adminhtml\Order\Create\Machines;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Session quote
     *
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $sessionQuote;

    /**
     * Product factory
     *
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;

    protected $isMultipleMachine;

    protected $subscriptionCourseResourceModel;

    /**
     * @var \Riki\MachineApi\Helper\Machine
     */
    protected $machineTypeHelper;

    /**
     * @var \Riki\MachineApi\Model\ResourceModel\B2CMachineSkus
     */
    protected $machineTypeResource;

    /**
     * @var \Riki\MachineApi\Model\B2CMachineSkusFactory
     */
    protected $machineTypeFactory;

    /**
     * @var \Riki\MachineApi\Model\B2CMachineSkus\ProductFactory
     */
    protected $machineTypeProductFactory;

    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subscriptionPageHelper;

    /**
     * @var \Riki\SubscriptionPage\Model\PriceBox
     */
    protected $priceBoxModel;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;


    /**
     * Grid constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\Config $catalogConfig
     * @param \Riki\SubscriptionCourse\Model\ResourceModel\Course $subscriptionCourseResourceModel
     * @param \Riki\MachineApi\Helper\Machine $machineTypeHelper
     * @param \Riki\MachineApi\Model\ResourceModel\B2CMachineSkus $machineTypeResource
     * @param \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
     * @param \Riki\MachineApi\Model\B2CMachineSkusFactory $machineTypeFactory
     * @param \Riki\MachineApi\Model\B2CMachineSkus\ProductFactory $machineTypeProductFactory
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Config $catalogConfig,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $subscriptionCourseResourceModel,
        \Riki\MachineApi\Helper\Machine $machineTypeHelper,
        \Riki\MachineApi\Model\ResourceModel\B2CMachineSkus $machineTypeResource,
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper,
        \Riki\MachineApi\Model\B2CMachineSkusFactory $machineTypeFactory,
        \Riki\MachineApi\Model\B2CMachineSkus\ProductFactory $machineTypeProductFactory,
        \Riki\SubscriptionPage\Model\PriceBox $priceBoxModel,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Model\Session\Quote $sessionQuote
    ) {
        $this->productFactory = $productFactory;
        $this->catalogConfig = $catalogConfig;
        $this->sessionQuote = $sessionQuote;
        $this->subscriptionCourseResourceModel = $subscriptionCourseResourceModel;
        $this->machineTypeHelper = $machineTypeHelper;
        $this->machineTypeFactory = $machineTypeFactory;
        $this->machineTypeProductFactory = $machineTypeProductFactory;
        $this->machineTypeResource = $machineTypeResource;
        $this->subscriptionPageHelper = $subscriptionPageHelper;
        $this->priceBoxModel = $priceBoxModel;
        $this->coreRegistry = $coreRegistry;
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
        $this->_emptyText = __('The selected products not allow you to buy any machine.');
        $this->setId('sales_order_create_machines_grid');
        $this->setRowInitCallback('order.productGridRowInit.bind(order)');
        $this->setRowClickCallback('order.productGridRowClick.bind(order)');
        $this->setCheckboxCheckCallback('order.productGridCheckboxCheck.bind(order)');
        $this->setDefaultSort('type_id');
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
        $quote = $this->sessionQuote->getQuote();
        $listTypesApplicable = $this->machineTypeHelper->getTypesApplicable($quote);
        $request = $this->sessionQuote->getData();
        $customerId = isset($request['customer_id'])?$request['customer_id']:null;
        if ($customerId == null) {
            return $this;
        }
        $machineTypeProductModel = $this->machineTypeProductFactory->create();
        $productCollection = $machineTypeProductModel->getCollection();

        if ($listTypesApplicable) {
            $productCollection->addFieldToFilter('main_table.type_id', ['in' => $listTypesApplicable]);
        } else {
            $productCollection->addFieldToFilter('main_table.type_id', ['null' => true]);
        }

        $productCollection->getSelect()->joinLeft(
            ['smt' => $productCollection->getTable('subscription_course_machine_type')],
            'smt.type_id = main_table.type_id'
        );
        $this->setCollection($productCollection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $quote = $this->sessionQuote->getQuote();
        if (!$quote->getId()) {
            return parent::_prepareColumns();
        }
        $courseId = $quote->getRikiCourseId();
        $subscriptionType = $this->subscriptionPageHelper->getSubscriptionType($courseId);
        if ($subscriptionType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
            $this->isMultipleMachine = true;
        } else {
            $this->isMultipleMachine = false;
        }
        $this->addColumn(
            'product_id',
            [
                'header' => __('ID'),
                'header_css_class' => 'col-product-id',
                'column_css_class' => 'col-product-id',
                'index' => 'product_id',
                'sortable'  => true,
            ]
        );

        $this->addColumn(
            'name',
            [
                'header' => __('Product'),
                'index' => 'name',
                'header_css_class' => 'col-product-name',
                'column_css_class' => 'col-product-name',
                'renderer' => \Riki\Subscription\Block\Adminhtml\Order\Create\Machines\Grid\Column\Renderer\ProductName::class,
                'filter_condition_callback' => [$this, 'filterProductByName']
            ]
        );

        $this->addColumn(
            'type_name',
            [
                'header' => __('Machine Type'),
                'index' => 'type_name'
            ]
        );

        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'index' => 'sku',
                'header_css_class' => 'col-product-sku',
                'column_css_class' => 'col-product-sku',
                'renderer' => \Riki\Subscription\Block\Adminhtml\Order\Create\Machines\Grid\Column\Renderer\ProductSku::class,
                'filter_condition_callback' => [$this, 'filterProductBySku']
            ]
        );

        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'column_css_class' => 'price',
                'type' => 'currency',
                'currency_code' => $this->getStore()->getCurrentCurrencyCode(),
                'rate' => $this->getStore()->getBaseCurrency()->getRate($this->getStore()->getCurrentCurrencyCode()),
                'course_id' => $courseId,
                'index' => 'price',
                'renderer' => \Riki\Subscription\Block\Adminhtml\Order\Create\Machines\Grid\Column\Renderer\Price::class,
                'filter_condition_callback' => [$this, 'filterProductByPrice']
            ]
        );

        $inProduct = [
            'filter' => false,
            'header' => __('Select'),
            'type' => 'checkbox',
            'name' => 'in_products',
            'values' => $this->_getSelectedProducts(),
            'index' => 'product_id',
            'sortable' => false,
        ];
        if ($this->isMultipleMachine) {
            $inProduct['renderer'] = \Riki\Subscription\Block\Adminhtml\Order\Create\Machines\Grid\Column\Renderer\Checked::class;
        }

        $this->addColumn(
            'in_products',
            $inProduct
        );

        $this->addColumn(
            'qty',
            [
                'filter' => false,
                'sortable' => false,
                'header' => __('Quantity'),
                'renderer' => \Riki\Subscription\Block\Adminhtml\Order\Create\Machines\Grid\Column\Renderer\Qty::class,
                'name' => 'qty',
                'inline_css' => 'qty',
                'type' => 'input',
                'validate_class' => 'validate-number',
                'index' => 'qty'
            ]
        );

        return $this;
    }

    protected function filterProductByName($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $productIds = [];
        $quote = $this->sessionQuote->getQuote();
        $listTypesApplicable = $this->machineTypeHelper->getTypesApplicable($quote);
        foreach ($listTypesApplicable as $type) {
            $machines = $this->machineTypeFactory->create()->getMachinesByMachineType($type);
            foreach ($machines as $machine) {
                $productIds[] = $machine['product_id'];
            }
        }
        $productCollection = $this->productFactory->create()->getCollection();
        $productCollection->addFieldToFilter('entity_id', ['in' => $productIds]);
        $productCollection->addFieldToFilter('name', ['like' => '%'.$value.'%'])->load();

        $finalIds = [];
        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($productCollection->getItems() as $product) {
            $finalIds[] = $product->getId();
        }
        $collection->addFieldToFilter('main_table.product_id', ['in' => $finalIds]);
        $this->setCollection($collection->load());
        return $this;
    }

    protected function filterProductBySku($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return $this;
        }

        $productIds = [];
        $quote = $this->sessionQuote->getQuote();
        $listTypesApplicable = $this->machineTypeHelper->getTypesApplicable($quote);
        foreach ($listTypesApplicable as $type) {
            $machines = $this->machineTypeFactory->create()->getMachinesByMachineType($type);
            foreach ($machines as $machine) {
                $productIds[] = $machine['product_id'];
            }
        }
        $productCollection = $this->productFactory->create()->getCollection();
        $productCollection->addFieldToFilter('entity_id', ['in' => $productIds]);
        $productCollection->addFieldToFilter('sku', ['like' => '%'.$value.'%'])->load();

        $finalIds = [];
        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($productCollection->getItems() as $product) {
            $finalIds[] = $product->getId();
        }
        $collection->addFieldToFilter('main_table.product_id', ['in' => $finalIds]);
        return $this;
    }

    protected function filterProductByPrice($collection, $column)
    {
        if (!$values = $column->getFilter()->getValue()) {
            return $this;
        }
        $priceFrom = $values['from'];
        $priceTo = $values['to'];

        $productIds = [];
        $quote = $this->sessionQuote->getQuote();
        $listTypesApplicable = $this->machineTypeHelper->getTypesApplicable($quote);
        foreach ($listTypesApplicable as $type) {
            $machines = $this->machineTypeFactory->create()->getMachinesByMachineType($type);
            foreach ($machines as $machine) {
                $productIds[] = $machine['product_id'];
            }
        }
        $courseId = $quote->getRikiCourseId();
        $frequencyId = $quote->getRikiFrequencyId();
        $this->coreRegistry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);
        $this->coreRegistry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);
        $productCollection = $this->productFactory->create()->getCollection();
        $productCollection->addFieldToFilter('entity_id', ['in' => $productIds])->load();
        $finalIds = [];
        foreach ($productCollection as $product) {
            $product = $this->productFactory->create()->load($product->getId());
            $product->setQty(1);
            $finalPrice = $this->priceBoxModel->getFinalProductPrice($product);
            if (!$finalPrice) {
                continue;
            }
            if ($finalPrice[0] >= $priceFrom && $finalPrice[0] <= $priceTo) {
                $finalIds[] = $product->getId();
            }
        }
        $collection->addFieldToFilter('main_table.product_id', ['in' => $finalIds])->load();
        $this->setCollection($collection);
        return $this;
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
            ['block' => 'machines_grid', '_current' => true, 'collapse' => null]
        );
    }

    /**
     * Retrieve quote store object
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        return $this->sessionQuote->getStore();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('profile/product/index', ['id' => $row->getId()]);
    }

    /**
     * Get selected products
     *
     * @return mixed
     */
    protected function _getSelectedProducts()
    {
        $products = $this->getRequest()->getPost('products', []);
        return $products;
    }
}
