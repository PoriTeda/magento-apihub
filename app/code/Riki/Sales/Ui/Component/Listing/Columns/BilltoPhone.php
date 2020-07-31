<?php
namespace Riki\Sales\Ui\Component\Listing\Columns;

class BilltoPhone extends \Magento\Ui\Component\Listing\Columns\Date
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
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;
    /**
     * @var \Magento\Framework\Api\FilterBuilder
     */
    protected $_filterBuilder;

    /**
     * BilltoPhone constructor.
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Stdlib\BooleanUtils $booleanUtils
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Stdlib\BooleanUtils $booleanUtils,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $timezone, $booleanUtils, $components, $data);
        $this->_orderRepository = $orderRepository;
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
                try {
                    $order = $this->_getOrderById($item['entity_id']);
                    if ($order) {
                        $item['billing_phone'] = $order->getBillingAddress()->getTelephone();
                    }
                } catch (\Exception $e) {
                    $item['collected_date'] = $e->getMessage();
                }
            }
        }
        return $dataSource;
    }

    /**
     * @param $order_id
     * @return bool
     */
    public function _getOrderById($orderId)
    {
        $criteria = $this->_searchCriteriaBuilder->addFilter('entity_id', $orderId )
            ->create();

        $orderCollection = $this->_orderRepository->getList($criteria);

        if($orderCollection->getTotalCount()) {
            return $orderCollection->getFirstItem();
        } else {
            return false;
        }
    }
}
