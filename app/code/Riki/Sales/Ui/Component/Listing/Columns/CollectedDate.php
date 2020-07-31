<?php
namespace Riki\Sales\Ui\Component\Listing\Columns;

class CollectedDate extends \Magento\Ui\Component\Listing\Columns\Date
{
    /**
     * @var \Magento\Framework\Stdlib\BooleanUtils
     */
    private $booleanUtils;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;
    /**
     * @var \Magento\Sales\Api\ShipmentRepositoryInterface
     */
    protected $_shipmentRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;
    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $_filterBuilder;

    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Stdlib\BooleanUtils $booleanUtils,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $timezone, $booleanUtils, $components, $data);
        $this->_orderRepository = $orderRepository;
        $this->_shipmentRepository = $shipmentRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_filterBuilder = $filterBuilder;
        $this->booleanUtils = $booleanUtils;
    }
    /**
     * {@inheritdoc}
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items']) && is_array($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item['collected_date'] = '';
            }
        }
        return $dataSource;
    }

    /**
     * Get order by id
     *
     * @param $orderId
     * @return bool|\Magento\Sales\Api\Data\OrderInterface
     */
    public function _getOrderById($orderId)
    {
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */
        $order = $this->_orderRepository->get($orderId);

        if ($order->getId()) {
            return $order;
        }

        return false;
    }

    /**
     * Get order collected date
     *
     * @param $orderId
     * @return bool
     */
    public function _getOrderCollectedDate($orderId)
    {
        $criteria = $this->_searchCriteriaBuilder->addFilters(
            [
                $this->_filterBuilder->setField('order_id')->setValue($orderId)->create(),
                $this->_filterBuilder->setField('payment_status')->setValue(\Riki\Sales\Model\ResourceModel\Sales\Grid\PaymentStatus::PAYMENT_COLLECTED)->create()
            ]
        )->create();

        $shipCollection = $this->_shipmentRepository
            ->getList($criteria)
            ->setOrder('payment_date');

        if ($shipCollection->getSize()) {
            return $shipCollection->getFirstItem()->getPaymentDate();
        }

        return false;
    }
}
