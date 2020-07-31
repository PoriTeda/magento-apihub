<?php

namespace Riki\SubscriptionMachine\Model;

use Magento\Framework\DataObject;
use \Riki\SubscriptionMachine\Api\CoffeeSubscriptionOrderManagementInterface;
use \Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use \Riki\SubscriptionMachine\Exception\InputException;

/**
 * Class CoffeeSubscriptionOrderManagement
 * @package Riki\SubscriptionMachine\Model
 */
class CoffeeSubscriptionOrderManagement implements CoffeeSubscriptionOrderManagementInterface
{
    const FIELD_CONSUMER_DB_CUSTOMER_ID = 'consumerdb_customer_id';
    const ORDER_COMMENT = 'The order status has been changed to "NOT_SHIPPED" by FUJI approval.';

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Riki\SubscriptionMachine\Logger\ApiLogger
     */
    protected $logger;

    /**
     * @var MonthlyFeeProfile\Validator
     */
    protected $subscriptionMachineApiValidator;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * CoffeeSubscriptionOrderManagement constructor.
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Riki\SubscriptionMachine\Logger\ApiLogger $logger
     * @param MonthlyFeeProfile\Validator $subscriptionMachineApiValidator
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Riki\SubscriptionMachine\Logger\ApiLogger $logger,
        \Riki\SubscriptionMachine\Model\MonthlyFeeProfile\Validator $subscriptionMachineApiValidator,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->subscriptionMachineApiValidator = $subscriptionMachineApiValidator;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function approve($consumerDbId)
    {
        $consumerObject = $this->dataObjectFactory->create();
        $consumerObject->setData('consumerdb_customer_id', $consumerDbId);
        $this->subscriptionMachineApiValidator->validateApproveRules($consumerObject);

        $orders = $this->getOrdersToBeApproved($consumerDbId);
        if (!$orders) {
            // phpcs:ignore MEQP2.Classes.ObjectInstantiation
            $inputException = new InputException(__('The system not found any order meets the conditions.'));
            $inputException->SetErrorCode(InputException::ERROR_CODE_NOT_FOUND_ANY_ORDER);
            throw $inputException;
        } else {
            foreach ($orders as $order) {
                /**
                 * @var \Magento\Sales\Model\Order $order
                 */
                $order->addStatusHistoryComment(self::ORDER_COMMENT, OrderStatus::STATUS_ORDER_NOT_SHIPPED);
                try {
                    // phpcs:ignore MEQP1.Performance.Loop
                    $this->orderRepository->save($order);
                    $this->logger->info(__(
                        'Coffee Subscription Order #%1 has been approved successfully',
                        $order->getIncrementId()
                    ));
                } catch (\Exception $exception) {
                    $this->logger->error(__('Can not approve Coffee Subscription Order #%1', $order->getIncrementId()));
                    $this->logger->error($exception);
                    // phpcs:ignore MEQP2.Classes.ObjectInstantiation
                    $inputException = new InputException(__('There is some thing wrong in the system.'));
                    $inputException->setErrorCode(InputException::ERROR_CODE_SYSTEM);
                    throw $inputException;
                }
            }

            return true;
        }
    }

    /**
     * Get orders should be approved
     * @param string $consumerDbId
     * @return \Magento\Sales\Api\Data\OrderInterface[]
     */
    public function getOrdersToBeApproved($consumerDbId)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('status', OrderStatus::STATUS_ORDER_PENDING_FOR_MACHINE, 'eq')
            ->addFilter('customer_consumer_db_id', $consumerDbId, 'eq')
            ->create();

        return $this->orderRepository->getList($searchCriteria)->getItems();
    }
}
