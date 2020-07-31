<?php

namespace Riki\NpAtobarai\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class TransactionManagement
 * @package Riki\NpAtobarai\Model
 */
class TransactionManagement implements \Riki\NpAtobarai\Api\TransactionManagementInterface
{
    /**
     * @var \Riki\NpAtobarai\Model\TransactionRepository
     */
    protected $transactionRepository;

    /**
     * @var \Magento\Framework\Api\Filter
     */
    protected $filter;

    /**
     * @var \Magento\Framework\Api\Search\FilterGroup
     */
    protected $filterGroup;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    protected $searchCriteriaInterface;

    /**
     * @var \Riki\Framework\Helper\Transaction\Database
     */
    protected $dbTransaction;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Riki\AutomaticallyShipment\Model\CreateShipment
     */
    protected $createShipment;

    /**
     * @var \Riki\NpAtobarai\Model\TransactionFactory
     */
    protected $transactionFactory;

    /**
     * TransactionManagement constructor.
     * @param TransactionRepository $transactionRepository
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaInterface
     * @param \Magento\Framework\Api\Search\FilterGroup $filterGroup
     * @param \Magento\Framework\Api\Filter $filter
     * @param \Riki\Framework\Helper\Transaction\Database $dbTransaction
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Riki\AutomaticallyShipment\Model\CreateShipment $createShipment
     * @param \Riki\NpAtobarai\Model\TransactionFactory $transactionFactory
     */
    public function __construct(
        \Riki\NpAtobarai\Model\TransactionRepository $transactionRepository,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaInterface,
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Magento\Framework\Api\Filter $filter,
        \Riki\Framework\Helper\Transaction\Database $dbTransaction,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Riki\AutomaticallyShipment\Model\CreateShipment $createShipment,
        \Riki\NpAtobarai\Model\TransactionFactory $transactionFactory
    ) {
        $this->searchCriteriaInterface = $searchCriteriaInterface;
        $this->transactionRepository = $transactionRepository;
        $this->filterGroup = $filterGroup;
        $this->filter = $filter;
        $this->dbTransaction = $dbTransaction;
        $this->orderRepository = $orderRepository;
        $this->createShipment = $createShipment;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * @param int $orderId
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException|\Magento\Framework\Exception\NoSuchEntityException|\Magento\Framework\Exception\NotFoundException
     */
    public function createTransactions($orderId)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($orderId);

        // Prepare shipment data before save into DB
        $shipmentsData = $this->createShipment->preparedShipmentsData($order);
        if (!$shipmentsData) {
            throw new NotFoundException(__(
                'Can\'t create Transaction for order #%1 due to this order has no shipment data',
                $order->getIncrementId()
            ));
        }

        $npTransactions = [];
        try {
            $this->dbTransaction->beginTransaction();

            /** @var \Magento\Sales\Model\Order\Shipment $shipment */
            foreach ($shipmentsData as $shipment) {
                // Not need to create transaction if shipment has grand_total = 0
                if ($shipment->getGrandTotal() == 0) {
                    continue;
                }

                /** @var \Riki\NpAtobarai\Model\Transaction $transactionModel */
                $transactionModel = $this->transactionFactory->create();
                $transactionModel->setOrderId($shipment->getOrderId());
                $transactionModel->setOrderShippingAddressId($shipment->getShippingAddressId());
                $transactionModel->setDeliveryType($shipment->getDeliveryType());
                $transactionModel->setWarehouse($shipment->getWarehouse());
                $transactionModel->setBilledAmount($shipment->getGrandTotal());

                // Set list of shipment items to field goods
                $goods = [];
                /** @var \Magento\Sales\Model\Order\Shipment\Item $shipmentItem */
                foreach ($shipment->getItems() as $shipmentItem) {
                    $orderItem = $shipmentItem->getOrderItem();
                    if ($orderItem->getParentItemId()) {
                        continue;
                    }

                    $goods[] = [
                        'goods_name' => $shipmentItem->getName(),
                        'goods_price' => (float)$shipmentItem->getPriceInclTax(),
                        'quantity' => $shipmentItem->getQty()
                    ];
                }
                foreach ($this->getGoodsMappingForNpApiRegisterOrder() as $key => $title) {
                    $goodsPrice = 0;
                    if ($shipment->getData($key)) {
                        if (in_array($key, ['shopping_point_amount', 'discount_amount'])) {
                            $goodsPrice = -$shipment->getData($key);
                        } elseif ($key == 'gw_price') {
                            $goodsPrice = $shipment->getData($key) + $shipment->getData('gw_tax_amount');
                        } else {
                            $goodsPrice = $shipment->getData($key);
                        }
                    }

                    $goods[] = [
                        'goods_name' => $title,
                        'goods_price' => $goodsPrice,
                        'quantity' => 1
                    ];
                }

                $transactionModel->setGoods(json_encode($goods, JSON_UNESCAPED_UNICODE));
                $npTransactions[] = $transactionModel->save();
            }

            $this->dbTransaction->commit();
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();
            throw new LocalizedException(__(
                'Can\'t create new Transaction due to ' . $e->getMessage()
            ));
        }

        return $npTransactions;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return \Riki\NpAtobarai\Api\Data\TransactionInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getOrderTransactions(\Magento\Sales\Model\Order $order):array
    {
        $filters[] = $this->filter
            ->setField('order_id')
            ->setValue($order->getId());

        $filterGroup[] = $this->filterGroup->setFilters($filters);
        $searchCriteria = $this->searchCriteriaInterface->setFilterGroups($filterGroup);
        $searchResults = $this->transactionRepository->getList($searchCriteria);
        return $searchResults->getItems();
    }

    /**
     * Get goods mapping for Np Api Register Order
     *
     * @return array
     */
    private function getGoodsMappingForNpApiRegisterOrder()
    {
        // Hard code goods_name as spec required
        return [
            'shipment_fee' => '送料（税込）',
            'payment_fee' => 'お支払手数料（税込）',
            'shopping_point_amount' => 'ポイント値引き',
            'discount_amount' => '値引',
            'gw_price' => '選択可能包装・のし'
        ];
    }
}
