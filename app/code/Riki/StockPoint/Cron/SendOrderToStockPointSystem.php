<?php

namespace Riki\StockPoint\Cron;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Riki\StockPoint\Logger\StockPointLogger;
use Riki\StockPoint\Model\Api\BuildStockPointPostData;
use Riki\Subscription\Api\ProfileRepositoryInterface;
use Riki\Subscription\Api\ProfileProductCartRepositoryInterface;

class SendOrderToStockPointSystem
{
    const STOCK_POINT_ORDER_CONFIRMATION_STATUS_SENT = 1;
    const STOCK_POINT_ORDER_CONFIRMATION_STATUS_WAITING = 0;

    /**
     * @var array
     */
    protected $profileDataToUpdateFields = [
        'next_delivery_date',
        'next_order_date',
        'stock_point_delivery_information'
    ];

    /**
     * @var BuildStockPointPostData
     */
    protected $buildStockPointPostData;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var ProfileProductCartRepositoryInterface
     */
    protected $profileProductCartRepository;

    /**
     * @var StockPointLogger
     */
    protected $stockPointLogger;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * SendOrderToStockPointSystem constructor.
     * @param BuildStockPointPostData $buildStockPointPostData
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param ProfileRepositoryInterface $profileRepository
     * @param ProfileProductCartRepositoryInterface $profileProductCartRepository,
     * @param StockPointLogger $stockPointLogger
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        BuildStockPointPostData $buildStockPointPostData,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        ProfileRepositoryInterface $profileRepository,
        ProfileProductCartRepositoryInterface $profileProductCartRepository,
        StockPointLogger $stockPointLogger,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->profileRepository = $profileRepository;
        $this->profileProductCartRepository = $profileProductCartRepository;
        $this->stockPointLogger = $stockPointLogger;
        $this->connection = $resourceConnection->getConnection('sales');
    }

    /**
     * Execute
     * @return void
     */
    public function execute()
    {
        $searchCriteria = $this->searchCriteriaBuilder->addFilter(
            'stock_point_delivery_bucket_id',
            new \Zend_Db_Expr('NULL'),
            'notnull'
        )->addFilter(
            'stock_point_bucket_order_confirmation_status',
            self::STOCK_POINT_ORDER_CONFIRMATION_STATUS_WAITING,
            'eq'
        )->create();
        $orders = $this->orderRepository->getList($searchCriteria);
        /** @var \Magento\Sales\Model\Order $order */
        foreach ($orders as $order) {
            $this->processUpdateBucketOrder($order);
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    protected function processUpdateBucketOrder($order)
    {
        $requestData = $this->prepareRequestData($order);
        $stockPointResponseData = $this->buildStockPointPostData->callApiConfirmBucketOrder($requestData);
        if ($stockPointResponseData['call_api'] == 'success') {
            $this->stockPointLogger->info(
                __(
                    'The order #%1 called API successfully.',
                    $order->getIncrementId()
                ),
                ['type' => StockPointLogger::LOG_TYPE_CRON_SEND_BUCKET_ORDER]
            );
            $profileDataToUpdate = $stockPointResponseData['data'];
            if (isset($profileDataToUpdate['comment_for_customer'])) {
                $profileDataToUpdate['stock_point_delivery_information'] = $profileDataToUpdate['comment_for_customer'];
            }
            $this->updateBucketDelivery($profileDataToUpdate, $order);
        } else {
            $this->stockPointLogger->info(
                __(
                    'The order #%1 can not send to Stock Point system - %2',
                    $order->getIncrementId(),
                    json_encode(['api_response_data' => $stockPointResponseData])
                ),
                ['type' => StockPointLogger::LOG_TYPE_CRON_SEND_BUCKET_ORDER]
            );
        }
    }

    /**
     * @param array $profileDataToUpdate
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    protected function updateBucketDelivery($profileDataToUpdate, $order)
    {
        $profileId = $order->getData('subscription_profile_id');
        try {
            $subscriptionProfile = $this->profileRepository->get($profileId);
        } catch (NoSuchEntityException $e) {
            $this->stockPointLogger->info(
                __(
                    'The order #%1 have been skipped because the profile #%2 does not exist.',
                    $order->getIncrementId(),
                    $profileId
                ),
                ['type' => StockPointLogger::LOG_TYPE_CRON_SEND_BUCKET_ORDER]
            );
            return;
        }

        if ($subscriptionProfile) {
            try {
                $this->connection->beginTransaction();

                /*sync data from stock point to Profile*/
                foreach ($profileDataToUpdate as $field => $value) {
                    if (in_array($field, $this->profileDataToUpdateFields)) {
                        $subscriptionProfile->setData($field, $value);
                    }
                }
                $this->profileRepository->save($subscriptionProfile);

                /*sync delivery date for subscription product data*/
                /*product cart data*/
                $profileProductData = $this->profileRepository->getListProductCart($profileId);

                if ($profileProductData->getTotalCount()) {
                    /** @var \Riki\Subscription\Model\ProductCart\ProductCart $productData */
                    foreach ($profileProductData->getItems() as $productData) {
                        /** @var \Riki\Subscription\Model\Data\ApiProductCart $productCartModel */
                        $productCartModel = $productData->getDataModel();
                        $productCartModel->setDeliveryDate($subscriptionProfile->getNextDeliveryDate());
                        if (isset($profileDataToUpdate['customer_delivery_date']) &&
                            $profileDataToUpdate['customer_delivery_date']
                        ) {
                            $productCartModel->setOriginalDeliveryDate($profileDataToUpdate['customer_delivery_date']);
                        }

                        $this->profileProductCartRepository->save($productCartModel);
                    }
                }

                $order->setData(
                    'stock_point_bucket_order_confirmation_status',
                    self::STOCK_POINT_ORDER_CONFIRMATION_STATUS_SENT
                );
                $this->orderRepository->save($order);
                $this->connection->commit();
                $this->stockPointLogger->info(
                    __(
                        'The order #%1 have been updated successfully with next_delivery_date: %2, next_order_date: %3',
                        $order->getIncrementId(),
                        $profileDataToUpdate['next_delivery_date'],
                        $profileDataToUpdate['next_order_date']
                    ),
                    ['type' => StockPointLogger::LOG_TYPE_CRON_SEND_BUCKET_ORDER]
                );
            } catch (\Exception $e) {
                $this->connection->rollBack();
                $this->stockPointLogger->error(
                    __(
                        'The order #%1 failed to update data.',
                        $order->getIncrementId()
                    ),
                    ['type' => StockPointLogger::LOG_TYPE_CRON_SEND_BUCKET_ORDER]
                );
                $this->stockPointLogger->critical($e, ['type' => StockPointLogger::LOG_TYPE_CRON_SEND_BUCKET_ORDER]);
            }
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return array
     */
    protected function prepareRequestData($order)
    {
        $appliedDiscountRate = 0;
        $orderItems = $order->getItems();
        /** @var \Magento\Sales\Model\Order\Item $item */
        foreach ($orderItems as $item) {
            if ($itemAppliedDiscountRate = $item->getData('stock_point_applied_discount_rate')) {
                $appliedDiscountRate = $itemAppliedDiscountRate;
                break;
            }
        }
        return [
            'profile_id' => $order->getData('subscription_profile_id'),
            'order_bucket_id' => $order->getData('stock_point_delivery_bucket_id'),
            'magento_order_id' => $order->getIncrementId(),
            'email' =>  $order->getData('customer_email'),
            'applied_discount_rate' => (int)$appliedDiscountRate
        ];
    }
}
