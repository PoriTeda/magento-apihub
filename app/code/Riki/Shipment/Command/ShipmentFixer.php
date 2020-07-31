<?php
/**
 * Riki Shipment Module
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Shipment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Shipment\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Magento\Sales\Model\Order\InvoiceDocumentFactory;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Framework\App\ResourceConnection;
use Riki\ShipmentImporter\Helper\Order as ShipmentHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
/**
 * Class ShipmentFixer
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Shipment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

class ShipmentFixer extends Command
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $criterialBuilder;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $state;
    /**
     * @var \Riki\BasicSetup\Helper\Data
     */
    protected $basicHelper;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    protected $invoiceService;
    /**
     * @var InvoiceDocumentFactory
     */
    protected $invoiceDocumentFactory;
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var InvoiceRepository
     */
    protected $invoiceRepository;
    /**
     * @var ResourceConnection
     */
    protected $connection ;
    /**
     * file name to get order
     */
    CONST ORDER_CSV_FILE = 'order_capture_failed.csv';
    /**
     * file path
     */
    const CONFIG_DATA_VERSION_PATH = '/code/Riki/Shipment/Data/';
    /**
     * @var ShipmentHelper
     */
    protected $shipmentHelper;
    /**
     * @var TimezoneInterface
     */
    protected $timezone;
    /**
     * @var DateTime
     */
    protected $datetime;

    /**
     * ShipmentFixer constructor.
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\State $state
     * @param \Riki\BasicSetup\Helper\Data $helper
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Sales\Model\Service\InvoiceService $invoiceService
     * @param InvoiceDocumentFactory $invoiceDocumentFactory
     * @param InvoiceRepository $invoiceRepository
     * @param ResourceConnection $connection
     * @param ShipmentHelper $shipmentHelper
     * @param TimezoneInterface $timezone
     * @param DateTime $dateTime
     */
    public function __construct(
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\State $state,
        \Riki\BasicSetup\Helper\Data $helper,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        InvoiceDocumentFactory $invoiceDocumentFactory,
        InvoiceRepository $invoiceRepository,
        ResourceConnection $connection,
        ShipmentHelper $shipmentHelper,
        TimezoneInterface $timezone,
        DateTime $dateTime
    )
    {
        $this->orderRepository = $orderRepository;
        $this->criterialBuilder = $criteriaBuilder;
        $this->logger = $logger;
        $this->state = $state;
        $this->basicHelper = $helper;
        $this->directoryList = $directoryList;
        $this->invoiceService = $invoiceService;
        $this->invoiceDocumentFactory = $invoiceDocumentFactory;
        $this->invoiceRepository = $invoiceRepository;
        $this->connection = $connection;
        $this->shipmentHelper = $shipmentHelper;
        $this->timezone = $timezone;
        $this->datetime = $dateTime;
        parent::__construct();
    }
    /**
     * Set param name for CLI
     */
    protected function configure()
    {
        $options = [];
        $this->setName('riki:shipment:fixer')
            ->setDescription('Fix some cases in shipment')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * Fix order capture_fail but capture success in Paygent
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return null
     */
    public function execute(InputInterface $input= null, OutputInterface $output = null)
    {
        $this->state->setAreaCode('crontab');
        $originDate = $this->timezone->formatDateTime($this->datetime->gmtDate(),\IntlDateFormatter::MEDIUM);
        $needDate = $this->datetime->gmtDate('Y-m-d H:i:s', $originDate);

        $fileName = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::APP)
                    .self::CONFIG_DATA_VERSION_PATH
                    .self::ORDER_CSV_FILE;
        $dataTemp= $this->basicHelper->getCsvContent($fileName,true);
        $data = $this->_compressData($dataTemp);
        $seachBuilder = $this->criterialBuilder
                        ->addFilter('increment_id',$data,'in')
                        ->addFilter('status', OrderStatus::STATUS_ORDER_CAPTURE_FAILED)
                        ->create();
        $orderCollection = $this->orderRepository->getList($seachBuilder);
        if($orderCollection->getTotalCount())
        {
            foreach($orderCollection->getItems() as $order)
            {
                if($order->getStatus()==OrderStatus::STATUS_ORDER_CAPTURE_FAILED)
                {

                    if (!$order->hasInvoices()) // have not invoice
                    {
                      $this->createInvoiceWithoutCapture($order);
                    }
                    //update
                    try
                    {
                        //update Order
                        $newStatus = OrderStatus::STATUS_ORDER_COMPLETE;
                        $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                        $order->setStatus($newStatus);
                        $order->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);
                        $order->addStatusToHistory($newStatus,__('Complete order by Shipment Fixer'),false);
                        $this->orderRepository->save($order);
                        //update shipment
                        //update payment status for all shipments in an order
                        $paymentStatusData = [
                            'payment_status'=> PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
                            'payment_date' => $needDate
                        ];
                        $this->shipmentHelper->massUpdateShipments($order->getEntityId(),$paymentStatusData);
                        $output->writeln("Fix order :".$order->getIncrementId()." successfully ");

                    }catch(\Exception $e)
                    {
                        $this->logger->critical($e);
                    }
                }
                else
                {
                    $output->writeln("Status of order :".$order->getIncrementId()." is not ".OrderStatus::STATUS_ORDER_CAPTURE_FAILED);
                }
            }
        }
        else
        {
            $output->writeln("Order not found");
        }
    }

    /**
     * @param $data
     * @return array
     */
    private function _compressData($data)
    {
        //convert data to single array
        $newArr = [];
        foreach($data as $_d)
        {
            if($_d[0])
            {
                $newArr[] = $_d[0];
            }
        }
        return $newArr;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    public function createInvoiceWithoutCapture(\Magento\Sales\Model\Order $order)
    {
        $connection = $this->connection->getConnection('sales');
        $invoice = $this->invoiceDocumentFactory->create($order);
        $this->invoiceRepository->save($invoice);
        $this->orderRepository->save($order);
        $connection->beginTransaction();
        try {
            $invoice->setState(\Magento\Sales\Model\Order\Invoice::STATE_PAID);
            $this->invoiceRepository->save($invoice);
            $this->orderRepository->save($order);
            $connection->commit();
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $connection->rollBack();
        }
    }
}