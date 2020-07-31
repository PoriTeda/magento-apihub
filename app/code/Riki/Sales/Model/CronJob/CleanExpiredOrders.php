<?php

namespace Riki\Sales\Model\CronJob;

use Magento\Store\Model\StoresConfig;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Filesystem\DirectoryList;
use Riki\SubscriptionCourse\Model\Course\Type;

class CleanExpiredOrders extends \Magento\Sales\Model\CronJob\CleanExpiredOrders
{
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $directoryWrite;

    /**
     * @var \Riki\Framework\Helper\Transaction\Database
     */
    protected $dbTransaction;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * CleanExpiredOrders constructor.
     * @param StoresConfig $storesConfig
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Riki\Framework\Helper\Transaction\Database $dbTransaction
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        StoresConfig $storesConfig,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $collectionFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Filesystem $filesystem,
        \Riki\Framework\Helper\Transaction\Database $dbTransaction
    ) {
        parent::__construct($storesConfig, $collectionFactory);
        $this->logger = $logger;
        $this->directoryWrite = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->dbTransaction = $dbTransaction;
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        /**
         * Check cron state in order to avoid overlap.
         */
        $this->checkCronRun();

        $lifetimes = $this->storesConfig->getStoresConfigByPath('sales/orders/delete_pending_after');
        foreach ($lifetimes as $storeId => $lifetime) {
            /** @var $orders \Magento\Sales\Model\ResourceModel\Order\Collection */
            $orders = $this->orderCollectionFactory->create();
            $orders->addFieldToFilter('main_table.store_id', $storeId);
            $orders->addFieldToFilter('main_table.status', Order::STATE_PENDING_PAYMENT);
            $orders->getSelect()->joinLeft(
                    ['profile' => $orders->getTable('subscription_profile')],
                    "main_table.subscription_profile_id = profile.profile_id",
                    []
                )->joinLeft(
                    ['course' => 'subscription_course'],
                    'profile.course_id = course.course_id',
                    ['subscription_type']
                )->where(
                    new \Zend_Db_Expr(
                        'TIME_TO_SEC(TIMEDIFF(CURRENT_TIMESTAMP, `updated_at`)) >= ' . $lifetime * 60
                    )
                )->where(
                new \Zend_Db_Expr(sprintf('course.subscription_type != "%s"' , Type::TYPE_MONTHLY_FEE))
            );

            try {
                /**
                 * @var $order \Magento\Sales\Model\Order
                 */
                foreach ($orders->getItems() as $order) {
                    $this->processCancelOrder($order, $lifetime);
                }
            } catch (\Exception $e) {
                $this->logger->error('Error cancelling deprecated orders: ' . $e->getMessage());
            }
        }

        $this->deleteLockFolder();
    }

    /**
     * Process cancel order
     * @param $order
     * @param $lifetime
     */
    public function processCancelOrder($order, $lifetime)
    {
        $this->dbTransaction->beginTransaction();

        $orderId = $order->getId();
        try {
            $order->cancel();
            $order->addStatusToHistory(
                \Riki\Sales\Model\ResourceModel\Sales\Grid\OrderStatus::STATUS_ORDER_CANCELED,
                __('Canceled by the cron sales clean order with lifetime '.$lifetime .' minutes' ),
                false
            );
            $order->save();
            $this->dbTransaction->commit();
        } catch (\Exception $e) {
            $this->dbTransaction->rollback();
            $this->logger->error(
                '[Order : ' . $orderId . ' ] . Error cancelling deprecated orders: ' . $e->getMessage()
            );
        }
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function checkCronRun()
    {
        $lockFolder = $this->directoryWrite->getRelativePath('lock/' . $this->getLockFileName());
        if ($this->directoryWrite->isExist($lockFolder)) {
            $message = __('Please wait, system have a same process is running and haven’t finish yet.');
            throw new \Magento\Framework\Exception\LocalizedException($message);
        }

        $this->directoryWrite->create($lockFolder);
    }

    /**
     * Delete lock folder
     */
    public function deleteLockFolder()
    {
        $lockFolder = $this->directoryWrite->getRelativePath('lock/' . $this->getLockFileName());
        $this->directoryWrite->delete($lockFolder);
    }

    /**
     * Each type of cutoff  email has a particular name.
     *
     * @return string
     */
    protected function getLockFileName()
    {
        $part = explode('\\', get_class($this));
        return strtolower(end($part)) . '.lock';
    }
}
