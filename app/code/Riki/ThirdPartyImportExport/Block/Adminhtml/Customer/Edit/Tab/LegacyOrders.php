<?php
namespace Riki\ThirdPartyImportExport\Block\Adminhtml\Customer\Edit\Tab;

use Magento\Customer\Controller\RegistryConstants;


class LegacyOrders extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\ThirdPartyImportExport\Model\OrderFactory
     */
    protected $orderFactory;

    /**
     * LegacyOrders constructor.
     *
     * @param \Riki\ThirdPartyImportExport\Model\OrderFactory $orderFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param array $data
     */
    public function __construct(
        \Riki\ThirdPartyImportExport\Model\OrderFactory $orderFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->orderFactory = $orderFactory;
        $this->customerRepository = $customerRepository;
        $this->registry = $registry;

        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Initialize
     */
    protected function _construct()
    {
        parent::_construct();

        $this->setId('thirdpartyimportexport_customer_order_legacy');
        $this->setDefaultSort('created_date desc');
        $this->setUseAjax(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getGridUrl()
    {
        return $this->getUrl('thirdpartyimportexport/customer/order', ['_current' => true]);
    }

    /**
     * Retrieve the Url for a specified order row.
     *
     * @param \Riki\ThirdPartyImportExport\Model\Order |\Magento\Framework\DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl('thirdpartyimportexport/order/view', ['id' => $row->getId()]);
    }

    /**
     * Apply various selection filters to prepare the sales order grid collection.
     *
     * @inheritdoc
     */
    protected function _prepareCollection()
    {
        $consumerId = 0;
        $customerId = $this->registry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        if ($customerId) {
            try {
                $customer = $this->customerRepository->getById($customerId);
                if ($customer->getId() && ($consumerId = $customer->getCustomAttribute('consumer_db_id'))) {
                    $consumerId = $consumerId->getValue();
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $this->_logger->warning($e);
            }
        }

        /**
         * @var \Riki\ThirdPartyImportExport\Model\ResourceModel\Order\Collection $collection
         */
        $collection = $this->orderFactory->create()->getCollection();
        $collection->addFieldToFilter('main_table.customer_code', $consumerId);
        $collection->addFieldToSelect('order_no')
            ->addFieldToSelect('customer_code')
            ->addFieldToSelect('created_datetime')
            ->addFieldToSelect('order_status')
            ->addFieldToSelect('payment_commission') // to calc grand total display on grid
            ->addFieldToSelect('plan_type'); // to calc grand total display on grid


        $collection->getSelect()->joinLeft(
            ['shipping' => 'riki_shipping'],
            'shipping.order_no = main_table.order_no' ,
            ['address_first_name', 'address_last_name']
        );
        $collection->addFieldToSelect(new \Zend_Db_Expr("CONCAT(main_table.last_name,' ',main_table.first_name)"), 'bill_name');
        $collection->addFieldToSelect(new \Zend_Db_Expr("CONCAT(shipping.address_last_name,' ',shipping.address_first_name)"), 'ship_name');
        $collection->getSelect()->group('main_table.order_no');

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * {@inheritdoc}
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'order_no',
            [
                'header' => __('Order'),
                'width' => '100',
                'index' => 'order_no'
            ]
        );


        $this->addColumn(
            'created_datetime',
            [
                'header' => __('Purchased'),
                'index' => 'created_datetime',
                'type' => 'datetime',
                'timezone' => false
            ]
        );

        $this->addColumn(
            'customer_code',
            [
                'header' => __('Customer Code'),
                'index' => 'customer_code',
            ]
        );

        $this->addColumn(
            'bill_name',
            [
                'header' => __('Bill-to Name'),
                'index' => 'bill_name',
            ]
        );

        $this->addColumn(
            'ship_name',
            [
                'header' => _('Ship-to Name'),
                'index' => _('ship_name'),
            ]
        );

        $this->addColumn(
            'payment_money',
            [
                'header' => __('Order Total'),
                'renderer' => 'Riki\ThirdPartyImportExport\Block\Adminhtml\Order\Grid\Renderer\OrderTotal',
                'filter' => false
            ]
        );

        $this->addColumn(
            'order_status',
            [
                'header' => __('Order Status'),
                'index' => 'order_status',
                'type' => 'options',
                'options' => [
                    0 => __('Pre Order'),
                    1 => __('Ordinary'),
                    2 => __('Canceled')
                ]
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Set custom filter for in banner salesrule flag
     *
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() == 'order_no') {
            $value = $column->getFilter()->getValue();
            $this->getCollection()->addFieldToFilter('main_table.order_no', ['like' => '%' . $value . '%']);
        } elseif ($column->getId() == 'created_datetime') {
            $value = $column->getFilter()->getValue();
            $this->getCollection()->addFieldToFilter('main_table.created_datetime', ['from' => $value['from'], 'to' => $value['to']]);
        } elseif ($column->getId() == 'bill_name') {
            $value = $column->getFilter()->getValue();
            $this->getCollection()->addFieldToFilter('bill_name', ['like' => '%' . $value . '%']);
        } elseif ($column->getId() == 'ship_name') {
            $value = $column->getFilter()->getValue();
            $this->getCollection()->addFieldToFilter('ship_name', ['like' => '%' . $value . '%']);
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }
}
