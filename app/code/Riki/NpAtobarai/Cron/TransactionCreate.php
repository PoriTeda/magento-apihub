<?php

namespace Riki\NpAtobarai\Cron;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Riki\Framework\Helper\Cron as CronLocker;

class TransactionCreate
{
    const FILE_NAME = 'riki_np_atobarai_transaction_create';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var CronLocker
     */
    protected $cronLockerHelper;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\CollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var \Riki\NpAtobarai\Api\TransactionManagementInterface
     */
    protected $transactionManagement;

    /**
     * CreateNpTransactionsForNewOrder constructor.
     *
     * @param \Psr\Log\LoggerInterface $logger
     * @param CronLocker $cronLockerHelper
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Riki\NpAtobarai\Api\TransactionManagementInterface $transactionManagement
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        CronLocker $cronLockerHelper,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Riki\NpAtobarai\Api\TransactionManagementInterface $transactionManagement
    ) {
        $this->logger = $logger;
        $this->cronLockerHelper = $cronLockerHelper;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->transactionManagement = $transactionManagement;
    }

    /**
     * Create dummy shipment to register order to NP system.
     *
     * {@inheritdoc}
     */
    public function execute()
    {
        // Check cron status to avoid overlap.
        $this->cronLockerHelper->setLockFileName(self::FILE_NAME);
        if ($this->cronLockerHelper->isLocked()) {
            $this->logger->info(
                $this->cronLockerHelper->getLockMessage()
            );
            return;
        }

        try {
            $this->cronLockerHelper->lockProcess();

            // Get list orders with conditions as below
            $orderCollection = $this->orderCollectionFactory->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('status', OrderStatus::STATUS_ORDER_PENDING_NP)
                ->addFieldToFilter('assignation', ['notnull' => true]);

            $orderCollection->getSelect()
                ->join(
                    ['sop' => 'sales_order_payment'],
                    'main_table.entity_id = sop.parent_id AND sop.method = "npatobarai"',
                    []
                )->joinLeft(
                    ['rat' => 'riki_np_atobarai_transaction'],
                    'main_table.entity_id = rat.order_id',
                    []
                );
            $orderCollection->addFieldToFilter('rat.order_id', ['null' => true]);
            // Loop for each order meets conditions above to create dummy shipment
            /** @var \Magento\Sales\Model\Order $order */
            foreach ($orderCollection as $order) {
                $orderNumber = $order->getIncrementId();
                if ($order->canShip()) {
                    $this->logger->info(__('Start creating NP Transaction for order #%1', $orderNumber));

                    // Prepare shipment data before save into DB
                    try {
                        $npTransactions = $this->transactionManagement->createTransactions($order->getId());

                        if ($npTransactions) {
                            $this->logger->info(__('Created NP Transaction for order #%1 successfully', $orderNumber));
                        } else {
                            $this->logger->info(__('Can\'t create NP Transaction for order #%1', $orderNumber));
                        }
                    } catch (NoSuchEntityException $e) {
                        $this->logger->info($e->getMessage());
                    } catch (NotFoundException $e) {
                        $this->logger->info($e->getMessage());
                    } catch (LocalizedException $e) {
                        $this->logger->critical($e);
                    } catch (\Exception $e) {
                        $this->logger->critical($e);
                    }
                } else {
                    $this->logger->info(__(
                        'Can\'t create Np Transaction for order #%1 due to this order can\'t create shipment',
                        $orderNumber
                    ));
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e);
        } finally {
            // Delete lock folder after cron has finished running.
            $this->cronLockerHelper->unLockProcess();
        }
    }
}
