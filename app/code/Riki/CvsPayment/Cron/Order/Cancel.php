<?php
/**
 * CvsPayment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\CvsPayment\Cron\Order;

use Magento\Framework\Api;
use Magento\Sales\Api as SalesApi;
use Riki\CvsPayment\Api\ConstantInterface;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;

/**
 * Class Cancel
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Cancel
{
    const BATCH_LIMIT = 1000;

    /**
     * DataHelper
     *
     * @var \Riki\CvsPayment\Helper\Data
     */
    protected $dataHelper;
    /**
     * OrderRepository
     *
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * SearchCriteriaBuilder
     *
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * Order Management
     *
     * @var \Magento\Sales\Api\OrderManagementInterface
     */
    protected $orderManagement;
    /**
     * Logger
     *
     * @var \Riki\CvsPayment\Logger\Order\Cancel
     */
    protected $logger;
    /**
     * EmailHelper
     *
     * @var \Riki\CvsPayment\Helper\Email
     */
    protected $emailHelper;

    /**
     * Cancel constructor.
     *
     * @param \Riki\CvsPayment\Helper\Email        $emailHelper           helper
     * @param \Riki\CvsPayment\Logger\Order\Cancel $logger                logger
     * @param SalesApi\OrderManagementInterface    $orderManagement       api
     * @param Api\FilterBuilder                    $filterBuilder         api
     * @param Api\SearchCriteriaBuilder            $searchCriteriaBuilder api
     * @param SalesApi\OrderRepositoryInterface    $orderRepository       repository
     * @param \Riki\CvsPayment\Helper\Data         $dataHelper            helper
     */
    public function __construct(
        \Riki\CvsPayment\Helper\Email $emailHelper,
        \Riki\CvsPayment\Logger\Order\Cancel $logger,
        \Magento\Sales\Api\OrderManagementInterface $orderManagement,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Riki\CvsPayment\Helper\Data $dataHelper
    ) {
        $this->emailHelper = $emailHelper;
        $this->orderManagement = $orderManagement;
        $this->dataHelper = $dataHelper;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->logger = $logger;
    }

    /**
     * Execute cronjob
     *
     * @return bool
     */
    public function execute()
    {
        $days = $this->dataHelper->getCancelDays();

        if (empty($days)) {
            return true;
        }

        $this->logger->info('Start cron-job "riki_cvspayment_cancel_order" ...');
        $orders = $this->getOrders();
        $this->logger->info(sprintf('Total orders: %d', $orders->getTotalCount()));
        if (!$orders->getTotalCount()) {
            return true;
        }

        foreach ($orders->getItems() as $order) {
            try {
                $this->logger
                    ->info(
                        sprintf(
                            'Order %d (%d)',
                            $order->getEntityId(),
                            $order->getIncrementId()
                        )
                    );
                $this->orderManagement->cancel($order->getEntityId());
            } catch (\Exception $e) {
                $this->logger->critical($e);
            }
        }

        $this->logger->info('Finish cron-job "riki_cvspayment_cancel_order"');

        $this->sendEmailReport($this->logger->getLogContent());

        return true;
    }

    /**
     * Send error report via email
     *
     * @param \Exception|string $log log
     *
     * @return void
     */
    public function sendEmailReport($log)
    {
        $receivers = $this->dataHelper->getCancelEmailNotification();
        $receivers = array_filter(explode(',', $receivers), 'trim');
        if (!$receivers) {
            return;
        }

        $this->emailHelper
            ->setTo($receivers)
            ->setBody(
                ConstantInterface::EMAIL_TEMPLATE_CANCEL_ORDER,
                ['log' => $log]
            )
            ->send();
    }

    /**
     * Get order will be process cancel
     *
     * @return \Magento\Sales\Api\Data\OrderSearchResultInterface
     */
    public function getOrders()
    {
        $days = (int)$this->dataHelper->getCancelDays();
        $daysExpr = sprintf('NOW() - INTERVAL %s DAY', $days);
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('flag_cvs', 1)
            ->addFilter('status', OrderStatus::STATUS_ORDER_PENDING_CVS)
            ->addFilter('csv_start_date', new \Zend_Db_Expr($daysExpr), 'lt')
            ->setPageSize(self::BATCH_LIMIT)
            ->create();
        $result =  $this->orderRepository->getList($searchCriteria);

        return $result;
    }
}
