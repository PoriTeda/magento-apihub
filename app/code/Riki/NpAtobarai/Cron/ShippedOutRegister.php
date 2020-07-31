<?php
namespace Riki\NpAtobarai\Cron;

use Exception;
use Psr\Log\LoggerInterface;
use Riki\Framework\Helper\Cron as CronLocker;
use Riki\Sales\Model\ResourceModel\Sales\Grid\ShipmentStatus;

class ShippedOutRegister
{
    const LOCK_FILE_NAME = 'riki_np_atobarai_transaction_shipped_out_register';

    /**
     * @var \Riki\NpAtobarai\Model\Method\Adapter
     */
    protected $adapter;

    /**
     * @var \Riki\NpAtobarai\Model\ResourceModel\Transaction\Collection
     */
    protected $transactionCollectionFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var CronLocker
     */
    protected $cronLockerHelper;

    /**
     * ShippedOutRegister constructor.
     * @param \Riki\NpAtobarai\Model\Method\Adapter $adapter
     * @param LoggerInterface $logger
     * @param CronLocker $cronLockerHelper
     * @param \Riki\NpAtobarai\Model\ResourceModel\Transaction\CollectionFactory $transactionCollectionFactory
     */
    public function __construct(
        \Riki\NpAtobarai\Model\Method\Adapter $adapter,
        LoggerInterface $logger,
        CronLocker $cronLockerHelper,
        \Riki\NpAtobarai\Model\ResourceModel\Transaction\CollectionFactory $transactionCollectionFactory
    ) {
        $this->adapter = $adapter;
        $this->transactionCollectionFactory = $transactionCollectionFactory;
        $this->logger = $logger;
        $this->cronLockerHelper = $cronLockerHelper;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        // Check cron status to avoid overlap.
        $this->cronLockerHelper->setLockFileName(self::LOCK_FILE_NAME);
        if ($this->cronLockerHelper->isLocked()) {
            $this->logger->info(
                $this->cronLockerHelper->getLockMessage()
            );
            return $this;
        }

        try {
            $this->cronLockerHelper->lockProcess();

            $transactions = $this->getTransactions();
            $groupTransaction = [];

            if (empty($transactions)) {
                return $this;
            }
            foreach ($transactions as $transaction) {
                $groupTransaction[$transaction->getOrderId()][] = $transaction;
            }

            foreach ($groupTransaction as $transactionList) {
                try {
                    $this->adapter->registerShipmentOut($transactionList);
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        } catch (Exception $e) {
            $this->logger->critical($e);
        } finally {
            // Delete lock folder after cron has finished running.
            $this->cronLockerHelper->unLockProcess();
        }
    }

    /**
     * @return \Magento\Framework\DataObject[]
     */
    private function getTransactions()
    {
        $transactionCollectionFactory = $this->transactionCollectionFactory->create();
        $connection = $transactionCollectionFactory->getConnection();
        $transactionCollection = $transactionCollectionFactory->addFieldToFilter(
            \Riki\NpAtobarai\Api\Data\TransactionInterface::IS_SHIPPED_OUT_REGISTERED,
            ['eq' => \Riki\NpAtobarai\Model\Transaction::NOT_REGISTERED_SHIPPED_OUT_YET]
        );
        $transactionCollection->getSelect()->join(
            ['sales_shipment' => $connection->getTableName('sales_shipment')],
            'sales_shipment.entity_id = main_table.shipment_id',
            ['shipment_status']
        )->where('sales_shipment.shipment_status in (?)',
            [ShipmentStatus::SHIPMENT_SHIPPED_OUT, ShipmentStatus::SHIPMENT_DELIVERY_COMPLETED]
        );
        return $transactionCollection->getItems();
    }
}
