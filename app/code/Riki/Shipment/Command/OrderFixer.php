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
use Symfony\Component\Console\Input\InputArgument;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus as OrderStatus;
use Riki\Shipment\Model\ResourceModel\Status\Options\Payment as PaymentStatus;
use Magento\Sales\Model\Order\InvoiceDocumentFactory;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Framework\App\ResourceConnection;
use Riki\ShipmentImporter\Helper\Order as ShipmentHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
/**
 * Class OrderFixer
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

class OrderFixer extends Command
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
     * @var State
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
    protected $connection;
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
     * Ticket ID
     */
    CONST TICKET_ID = 'ticket_id';
    /**
     * CSV file location
     */
    CONST CSV_FILE = 'csv_file';

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
        $options = [
            new InputArgument(
                self::TICKET_ID,
                InputArgument::REQUIRED,
                'Ticket ID'
            ),
            new InputArgument(
                self::CSV_FILE,
                InputArgument::REQUIRED,
                'CSV file to import'
            ),
        ];
        $this->setName('riki:order:fixer')
            ->setDescription('Update order in some incorrect cases')
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
    public function execute(InputInterface $input = null, OutputInterface $output = null)
    {
        $tickets = [2137, 2272];
        $path = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $this->state->setAreaCode('crontab');
        $ticketNumber = $input->getArgument(self::TICKET_ID);
        if (!in_array(intval($ticketNumber), $tickets)) {
            $output->writeln("Ticket $ticketNumber is incorrect. Please try again");
            return;
        }
        $csvFileName = $input->getArgument(self::CSV_FILE);
        $csvFile = $path . DIRECTORY_SEPARATOR . $csvFileName;
        //validate csv file
        if (!$this->basicHelper->checkFileExist('var/' . $csvFileName)) {
            $output->writeln("Csv file $csvFileName does not exist. Please try again");
            return;
        }
        //execute task
        $datas = $this->basicHelper->getCsvContent($csvFile, true);
        if (empty($datas)) {
            $output->writeln("Csv file $csvFileName is empty");
        } else {
            $indexKey = 1;
            $output->writeln("Start processing data");
            switch ($ticketNumber) {
                case 2137:
                    $indexKey = 1;
                    break;
                case 2272:
                    $indexKey = 0;
                    break;
            }
            $this->executeUpdate($datas, $indexKey, $input, $output);
        }

    }

    /**
     * @param array $datas
     * @param int $indexKey
     * @param InputInterface|null $input
     * @param OutputInterface|null $output
     */
    protected function executeUpdate(array $datas, int $indexKey, InputInterface $input = null, OutputInterface $output = null)
    {
        $originDate = $this->timezone->formatDateTime($this->datetime->gmtDate(), \IntlDateFormatter::MEDIUM);
        $needDate = $this->datetime->gmtDate('Y-m-d H:i:s', $originDate);
        foreach ($datas as $_data) {
            if (array_key_exists($indexKey, $_data)) {
                $orderNumber = $_data[$indexKey];
                $orderObject = $this->getOrderByIncrementId($orderNumber);
                if ($orderObject) {
                    // process order
                    if ($orderObject->getStatus() == OrderStatus::STATUS_ORDER_COMPLETE) {
                        $output->writeln("Order: $orderNumber is already complete.");
                    } else {
                        //create invoice if It has not
                        if (!$orderObject->hasInvoices()) {
                            $invoice = $this->invoiceDocumentFactory->create($orderObject);
                            $invoice->setState(\Magento\Sales\Model\Order\Invoice::STATE_PAID);
                            $this->invoiceRepository->save($invoice);
                        }
                        $orderObject->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                        $orderObject->setStatus(OrderStatus::STATUS_ORDER_COMPLETE);
                        $orderObject->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);
                        $orderObject->setCollectionDate($needDate);
                        $orderObject->setPaymentDate($needDate);
                        $orderObject->addStatusToHistory(
                            OrderStatus::STATUS_ORDER_COMPLETE,
                            __('Complete by Order fixer'),
                            false
                        );
                        $repeat = true;
                        while ($repeat) {
                            try {
                                $this->orderRepository->save($orderObject);
                                $output->writeln("Order: $orderNumber has been updated successfully.");
                                $repeat = false;
                            } catch (\Exception $e) {
                                if ($this->checkDeadlock($e)) {
                                    $repeat = true;
                                } else {
                                    $repeat = false;
                                }
                                $this->logger->critical($e);
                            }
                        }
                    }
                } else {
                    $output->writeln("Order: $orderNumber does not exist");
                }
            } else {
                $output->writeln("Value in this row is invalid");
            }
        }
    }

    /**
     * @param $incrementId
     * @return bool
     */
    protected function getOrderByIncrementId($incrementId)
    {
        $searchCriteria = $this->criterialBuilder
            ->addFilter('increment_id', $incrementId)
            ->create();
        $orderCollection = $this->orderRepository->getList($searchCriteria);
        if ($orderCollection->getTotalCount()) {
            return $orderCollection->getFirstItem();
        } else {
            return false;
        }
    }

    /**
     * @param $e
     * @return bool
     */
    protected function checkDeadlock($e)
    {
        if (
            preg_match('#SQLSTATE\[HY000\]: [^:]+: 1205[^\d]#', $e->getMessage()) ||
            preg_match('#SQLSTATE\[40001\]: [^:]+: 1213[^\d]#', $e->getMessage())
        ) {
            return true;
        } else {
            return false;
        }

    }
}