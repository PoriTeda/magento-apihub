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
use Riki\Shipment\Model\ResourceModel\Status\Options\Shipment as ShipmentStatus;
use Magento\Sales\Model\Order\InvoiceDocumentFactory;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Framework\App\ResourceConnection;
use Riki\ShipmentImporter\Helper\Order as ShipmentHelper;
use Riki\ShipmentImporter\Helper\Data as ShipmentDataHelper;
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

class OrderUpdate extends Command
{
    const TASK_ORDER_B2B_FIXER = 'order-b2b-fixer';

    const TASK_ORDER_PARTIAL_COMPLETE = 'order-partial-complete';

    protected $shipmentRepository;
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
     * @var
     */
    protected $shipmentDataHelper;
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
    CONST TASK_NAME = 'task_name';
    /**
     * CSV file location
     */
    CONST CSV_FILE = 'csv_file';

    protected $customerHelper;

    protected $orderAddressRepository;
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
        \Magento\Sales\Api\ShipmentRepositoryInterface $shipmentRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $criteriaBuilder,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository,
        \Riki\Customer\Helper\Data $customerHelper,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\State $state,
        \Riki\BasicSetup\Helper\Data $helper,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        InvoiceDocumentFactory $invoiceDocumentFactory,
        InvoiceRepository $invoiceRepository,
        ResourceConnection $connection,
        ShipmentHelper $shipmentHelper,
        ShipmentDataHelper $shipmentDataHelper,
        TimezoneInterface $timezone,
        DateTime $dateTime
    ) {
        $this->orderRepository = $orderRepository;
        $this->shipmentRepository = $shipmentRepository;
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
        $this->shipmentDataHelper = $shipmentDataHelper;
        $this->timezone = $timezone;
        $this->datetime = $dateTime;
        $this->customerHelper = $customerHelper;
        $this->orderAddressRepository = $orderAddressRepository;
        parent::__construct();
    }

    /**
     * Set param name for CLI
     */
    protected function configure()
    {
        $options = [
            new InputArgument(
                self::TASK_NAME,
                InputArgument::REQUIRED,
                'Task name'
            ),
            new InputArgument(
                self::CSV_FILE,
                InputArgument::OPTIONAL,
                'CSV file to import'
            ),
        ];
        $this->setName('riki:order:update')
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
        $path = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $this->state->setAreaCode('crontab');
        $taskName = $input->getArgument(self::TASK_NAME);
        $csvFileName = $input->getArgument(self::CSV_FILE);
        $csvFile = $path . DIRECTORY_SEPARATOR . $csvFileName;
        //validate csv file
        if (!$this->basicHelper->checkFileExist('var/' . $csvFileName)) {
            $output->writeln("Csv file $csvFileName does not exist. Please try again");
            return;
        }
        switch ($taskName) {
            case self::TASK_ORDER_B2B_FIXER:
                $data = [$taskName,$path];
                break;
            case self::TASK_ORDER_PARTIAL_COMPLETE: //RIM-6816
                $data = $this->basicHelper->getCsvContent($csvFile, true, true, 0, 6);
                break;
            default:
                $data = $this->basicHelper->getCsvContent($csvFile, true);
                break;
        }
        //execute task
        if (empty($data)) {
            $output->writeln("Csv file $csvFileName is empty");
        } else {
            $output->writeln("Start processing data");
            switch ($taskName) {
                case 'order-cancel':
                    $this->doCancel($data, $output);
                    break;
                case 'shipment-reject':
                    $this->doRejectShipment($data, $output);
                    break;
                case 'order-cod-fixer':
                    $this->doInvoiceOrder($data, $output);
                    break;
                case 'order-cod-complete': //RIM-5884, RIM-5886
                    $shouldInvoice = true;
                    $dataStatus = [
                        'shipment_status'=> ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
                        'payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
                        'order_payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
                        'ticket_number'=> '5884'
                    ];
                    $this->doCompleteOrders($data, $dataStatus, $output, $shouldInvoice);
                    break;
                case 'order-free-complete': //RIM-5883
                    $shouldInvoice = true;
                    $dataStatus = [
                        'shipment_status'=> ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
                        'payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_NOT_APPLICABLE,
                        'order_payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
                        'ticket_number'=> '5883',
                        'force_payment_status' => true,
                    ];
                    $this->doCompleteOrders($data, $dataStatus, $output, $shouldInvoice);
                    break;
                case 'order-reject-complete': //RIM-5885
                    $shouldInvoice = true;
                    $dataStatus = [
                        'shipment_status'=> ShipmentStatus::SHIPMENT_STATUS_REJECTED,
                        'payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_NOT_APPLICABLE,
                        'order_payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
                        'force_payment_status' => true,
                        'ticket_number'=> '5884'
                    ];
                    $this->doCompleteOrders($data, $dataStatus, $output, $shouldInvoice);
                    break;
                case 'order-complete': //RIM-5882 free + cvs payment
                    $shouldInvoice = true;
                    $dataStatus = [
                        'shipment_status'=> ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
                        'payment_status' => '',
                        'order_payment_status' => '',
                        'force_payment_status' => true,
                        'ticket_number'=> '5882'
                    ];
                    $this->doCompleteOrders($data, $dataStatus, $output, $shouldInvoice);
                    break;
                case 'rim-6235':
                    $shouldInvoice = true;
                    $dataStatus = [
                        'shipment_status'=> ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
                        'payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
                        'order_payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
                        'force_payment_status' => true,
                        'ticket_number'=> 'RIM-6235'
                    ];
                    $this->doCompleteOrders($data, $dataStatus, $output, $shouldInvoice);
                    break;
                case 'rim-6169': //update order only, not update shipments
                    $shouldInvoice = true;
                    $dataStatus = [
                        'shipment_status'=> ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
                        'payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
                        'force_payment_status' => true,
                        'ticket_number'=> 'RIM-6169',
                        'data_key' => 'incrementID',
                        'reject_shipment' => true
                    ];
                    $this->doCompleteOrders($data, $dataStatus, $output, $shouldInvoice);
                    break;
                case 'rim-6247':
                    $shouldInvoice = true;
                    $dataStatus = [
                        'shipment_status'=> ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
                        'payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
                        'order_payment_status' => false,
                        'force_payment_status' => true,
                        'ticket_number'=> 'RIM-6247',
                        'data_key' => 'incrementID',
                        'reject_shipment' => true
                    ];
                    $this->doCompleteOrders($data, $dataStatus, $output, $shouldInvoice);
                    break;
                case 'rim-6177':
                    $shouldInvoice = true;
                    $dataStatus = [
                        'shipment_status'=> ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
                        'payment_status' => '',
                        'order_payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
                        'force_payment_status' => true,
                        'ticket_number'=> '6177',
                        'data_key' => 'increment_id'
                    ];
                    $this->doCompleteOrders($data, $dataStatus, $output, $shouldInvoice);
                    break;
                case 'rim-6262':
                    $shouldInvoice = true;
                    $dataStatus = [
                        'shipment_status'=> ShipmentStatus::SHIPMENT_STATUS_DELIVERY_COMPLETED,
                        'payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
                        'order_payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
                        'force_payment_status' => true,
                        'ticket_number'=> 'RIM-6262',
                        'data_key' => 'incrementId'
                    ];
                    $this->doCompleteOrders($data, $dataStatus, $output, $shouldInvoice);
                    break;
                case self::TASK_ORDER_PARTIAL_COMPLETE: //RIM-6816
                    $shouldInvoice = true;
                    $dataStatus = [
                        'shipment_status'=> ShipmentStatus::SHIPMENT_STATUS_REJECTED,
                        'payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_NOT_APPLICABLE,
                        'order_payment_status' => PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED,
                        'force_payment_status' => true,
                        'ticket_number'=> 'RIM-6816',
                        'data_key' => 'incrementId'
                    ];
                    $this->doCompleteOrders($data, $dataStatus, $output, $shouldInvoice, true, 6);
                    break;
                case 'order-cvs-fixer':
                    $this->doCVSOrder($data, $output);
                    break;
                case 'order-cc-fixer':
                    $this->doCCOrder($data, $output);
                    break;
                case 'order-b2b-fixer':
                    $this->doUpdateB2bShipment($output);
                    break;
                default:
                    $output->writeln('Invalid task');
                    break;
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
     * @param $entityId
     * @return bool|\Magento\Sales\Api\Data\OrderInterface
     */
    protected function getOrderByEntityId($entityId)
    {
        try{
            return $this->orderRepository->get($entityId);
        }catch(\Exception $e){
            $this->logger->info($e->getMessage());
            return false;
        }
    }
    /**
     * @param $incrementId
     * @return bool
     */
    protected function getShipmentByIncrementId($incrementId)
    {
        $searchCriteria = $this->criterialBuilder
            ->addFilter('increment_id', $incrementId)
            ->create();
        $shipmentCollection = $this->shipmentRepository->getList($searchCriteria);
        if ($shipmentCollection->getTotalCount()) {
            return $shipmentCollection->getFirstItem();
        } else {
            return false;
        }
    }

    /**
     * @param array $data
     */
    protected function doCancel(array $data, OutputInterface $output = null)
    {
        if (!empty($data)) {
            foreach ($data as $_data) {
                $orderNumber = $_data[0];
                if ($orderNumber) {
                    $output->writeln("Cancel order: ".$orderNumber);
                    $orderObject = $this->getOrderByIncrementId($orderNumber);
                    if ($orderObject) {
                        $this->cancelOrder($orderObject);
                    }
                } else {
                    $output->writeln("Order does not exist: ".$orderNumber);
                }
            }
        }
    }
    /**
     * @param \Magento\Sales\Model\Order $order
     */
    protected function cancelOrder(\Magento\Sales\Model\Order $order)
    {
        try{
            $order->addStatusToHistory(
                OrderStatus::STATUS_ORDER_CANCELED,
                'Cancel by Order Fixer',
                false
            );
            $order->setState(\Magento\Sales\Model\Order::STATE_CANCELED);
            $order->setStatus(OrderStatus::STATUS_ORDER_CANCELED);
            $order->save();
        }catch (\Exception $e)
        {
            $this->logger->critical($e);
        }
    }

    /**
     * Command to reject Shipment
     *
     * @param array $data
     * @param OutputInterface|null $output
     */
    protected function doRejectShipment(array $data, OutputInterface $output = null)
    {
        $needSave = true;
        if($data)
        {
            foreach ($data as $_data) {
                $shipmentNumber = $_data[0];
                if($shipmentNumber)
                {
                    $shipmentObject = $this->getShipmentByIncrementId($shipmentNumber);
                    if($shipmentObject)
                    {
                        try{
                            $output->writeln("Reject shipment: ".$shipmentNumber);
                            $shipmentObject->setShipmentStatus(ShipmentStatus::SHIPMENT_STATUS_REJECTED);
                            $shipmentObject->save();
                            $order = $shipmentObject->getOrder();
                            $finalStatus = $this->shipmentHelper->getCurrentShipmentStatusOrder($order);
                            switch ($finalStatus)
                            {
                                case ShipmentHelper::STEP_DELIVERY_COMPLETED:
                                    $order->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                                    $order->setStatus(OrderStatus::STATUS_ORDER_COMPLETE);
                                    $this->shipmentDataHelper->createInvoiceOrder($order);
                                    $needSave = false;
                                    break;
                                case ShipmentHelper::STEP_SHIPPED_ALL:
                                    if($order->getFreeOfCharge())
                                    {
                                        $order->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);
                                    }
                                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                                    $order->setStatus(OrderStatus::STATUS_ORDER_SHIPPED_ALL);
                                    $needSave = true;
                                    break;
                                case ShipmentHelper::STEP_PARTIALL_SHIPPED:
                                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                                    $order->setStatus(OrderStatus::STATUS_ORDER_PARTIALLY_SHIPPED);
                                    $needSave = true;
                                    break;
                            }
                            if($needSave){
                                $order->save();
                            }
                        }catch(\Exception $e)
                        {
                            $this->logger->critical($e);
                        }
                    }
                    else
                    {
                        $output->writeln("Shipment does not exist: ".$shipmentNumber);
                    }
                }
            }
        }
    }

    /**
     * Command to create invoice for order
     *
     * @param array $data
     * @param OutputInterface|null $output
     */
    protected function doInvoiceOrder(array $data, OutputInterface $output = null)
    {
        if($data)
        {
            foreach ($data as $_data) {
                $orderNumber = $_data[0];
                if($orderNumber)
                {
                    $orderObject = $this->getOrderByIncrementId($orderNumber);
                    if($orderObject)
                    {
                        if(!$orderObject->hasInvoices())
                        {
                            $output->writeln("Create invoice order: ".$orderNumber);
                            $this->shipmentDataHelper->createInvoiceOrderOnly($orderObject, '',true);
                        }
                        else
                        {
                            try{
                                $orderObject->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                                $orderObject->setStatus(OrderStatus::STATUS_ORDER_COMPLETE);
                                $orderObject->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);
                                //add history
                                $orderObject->addStatusToHistory(
                                    OrderStatus::STATUS_ORDER_COMPLETE,
                                    __('Complete by Order fixer'),
                                    false
                                );
                                $orderObject->save();
                            }catch (\Exception $e)
                            {
                                $this->logger->info($e->getMessage());
                            }
                        }
                        $this->orderShipmentCollected($orderObject);
                    }
                }
                else
                {
                    $output->writeln("Order does not exist: ".$orderNumber);
                }

            }
        }
    }

    /**
     * Fix CVS order data
     *
     * @param array $data
     * @param OutputInterface|null $output
     */
    protected function doCVSOrder(array $data, OutputInterface $output = null)
    {
        if($data)
        {
            foreach ($data as $_data) {
                $orderNumber = $_data[0];
                if($orderNumber)
                {
                    $orderObject = $this->getOrderByIncrementId($orderNumber);
                    if($orderObject)
                    {
                        $orderObject->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);
                        if(!$orderObject->hasInvoices())
                        {
                            $output->writeln("Create invoice order: ".$orderNumber);
                            $this->shipmentDataHelper->createInvoiceOrderOnly($orderObject, '',false);
                        }
                        else
                        {
                            try{
                                $orderObject->save();
                            }catch (\Exception $e)
                            {
                                $this->logger->info($e->getMessage());
                            }
                        }
                        $this->orderShipmentCollected($orderObject);
                    }
                    else
                    {
                        $output->writeln("Order does not exist: ".$orderNumber);
                    }
                }
                else
                {
                    $output->writeln("Order does not exist: ".$orderNumber);
                }

            }
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     */
    public function orderShipmentCollected(\Magento\Sales\Model\Order $order)
    {
        $shipmentCollection = $order->getShipmentsCollection();
        if($shipmentCollection->getTotalCount())
        {
            try{
                foreach($shipmentCollection as $shipment)
                {
                    $shipment->setOrderStatus($order->getStatus());
                    $shipment->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);
                    $shipment->save();
                }
            }catch(\Exception $e)
            {
                $this->logger->info($e->getMessage());
            }
        }

    }

    /**
     * Fix COD order data
     *
     * @param array $data
     * @param OutputInterface|null $output
     */
    protected function doCCOrder(array $data, OutputInterface $output = null)
    {
        if($data)
        {
            foreach ($data as $_data) {
                $orderNumber = $_data[0];
                if($orderNumber)
                {
                    $orderObject = $this->getOrderByIncrementId($orderNumber);
                    if($orderObject)
                    {
                        $orderObject->setPaymentStatus(PaymentStatus::SHIPPING_PAYMENT_STATUS_PAYMENT_COLLECTED);
                        if(!$orderObject->hasInvoices())
                        {
                            $output->writeln("Create invoice order: ".$orderNumber);
                            if($orderObject->getStatus()==OrderStatus::STATUS_ORDER_COMPLETE)
                            {
                                $needComplete = false;
                            }
                            else
                            {
                                $needComplete = true;
                            }
                            $this->shipmentDataHelper->createInvoiceOrderOnly($orderObject, '',$needComplete);
                        }
                        else
                        {
                            try{
                                $orderObject->save();
                            }catch (\Exception $e)
                            {
                                $this->logger->info($e->getMessage());
                            }
                        }
                        $this->orderShipmentCollected($orderObject);
                    }
                    else
                    {
                        $output->writeln("Order does not exist: ".$orderNumber);
                    }
                }
                else
                {
                    $output->writeln("Order does not exist: ".$orderNumber);
                }

            }
        }
    }

    /**
     * Update b2b Shipments
     *
     * @param OutputInterface|null $output
     */
    public function doUpdateB2bShipment(OutputInterface $output = null)
    {
        $searchCriteria = $this->criterialBuilder
            ->addFilter('shipment_status', 'created')
            ->create();
        $shipmentsCollection = $this->shipmentRepository->getList($searchCriteria);
        if($shipmentsCollection->getTotalCount())
        {
            foreach($shipmentsCollection->getItems() as $shipment)
            {
                $shippingAddressId = $shipment->getShippingAddressId();
                $shippingAddress = $this->orderAddressRepository->get($shippingAddressId);
                $shippingStreet = implode(' ',$shippingAddress->getStreet());
                $shippingAddressSearch = [$shippingAddress->getFirstname(), $shippingAddress->getLastname(),$shippingStreet];
                $b2bFlag = $this->customerHelper->getB2bFlagValue($shippingAddressSearch);
                $shipment->setData('customer_b2b_flag',$b2bFlag)->save();
                $output->writeln("Update 2b2 flag for shipment: ".$shipment->getIncrementId());
            }
        }
    }
    /**
     * Fix data to complete order
     *
     * @param array $data
     * @param array $dataStatus
     * @param OutputInterface|null $output
     * @param bool $shouldInvoice
     * @param bool $partialShipments
     * @param $partialKeyColumn
     */
    protected function doCompleteOrders(
        array $data,
        array $dataStatus,
        OutputInterface $output = null,
        $shouldInvoice = false,
        $partialShipments= false,
        $partialKeyColumn
    )
    {
        if($data)
        {
            foreach ($data as $_data) {
                //increment_id
                $orderNumber = $_data[0];
                if(array_key_exists('data_key', $dataStatus)){
                    $orderObject = $this->getOrderByIncrementId($orderNumber);
                }else{
                    $orderObject = $this->getOrderByEntityId(intval($orderNumber));
                }
                if($orderNumber)
                {
                    if($orderObject)
                    {
                        $output->writeln("Process order : ".$orderNumber);
                        //correct shipment
                        if(!array_key_exists('reject_shipment',$dataStatus)){
                            $partialData = [];
                            if (isset($_data[$partialKeyColumn])) {
                                $partialData = explode(';', $_data[$partialKeyColumn]);
                            }
                            $this->updateShipments($orderObject->getId(), $dataStatus, $partialShipments, $partialData);
                        }
                        if(!$orderObject->hasInvoices() && $shouldInvoice)
                        {
                            $output->writeln("Create invoice order: ".$orderNumber);
                            $this->shipmentDataHelper->createInvoiceOrderOnly($orderObject, '',true);
                        }
                        else
                        {
                            try{
                                $orderObject->setState(\Magento\Sales\Model\Order::STATE_COMPLETE);
                                $orderObject->setStatus(OrderStatus::STATUS_ORDER_COMPLETE);
                                if($dataStatus['order_payment_status']){
                                    $orderObject->setPaymentStatus($dataStatus['order_payment_status']);
                                }
                                //add history
                                $orderObject->addStatusToHistory(
                                    OrderStatus::STATUS_ORDER_COMPLETE,
                                    __('Complete by Order fixer -- '.$dataStatus['ticket_number']),
                                    false
                                );
                                $orderObject->save();
                            }catch (\Exception $e)
                            {
                                $this->logger->info($e->getMessage());
                            }
                        }
                    }else{
                        $output->writeln("Order does not exist: ".$orderNumber);
                    }
                }
                else
                {
                    $output->writeln("Order does not exist: ".$orderNumber);
                }

            }
        }
    }

    /**
     * Update Shipment when order complete
     *
     * @param $orderId
     * @param array $dataStatus
     * @param bool $partialShipments
     * @param array $partialData
     */
    protected function updateShipments(
        $orderId,
        $dataStatus = [],
        $partialShipments = false,
        $partialData = []
    ){
        $shipmentStatus = $dataStatus['shipment_status'];
        $paymentStatus = $dataStatus['payment_status'];
        if ($partialShipments && $partialData) {
            $criterial = $this->criterialBuilder->addFilter(
                'order_id', $orderId
            )->addFilter(
                'ship_zsim', 1,'neq'
            )->addFilter(
                'is_chirashi', 1,'neq'
            )->addFilter(
                'increment_id', $partialData,'in'
            )->create();

        } else {
            $criterial = $this->criterialBuilder->addFilter(
                'order_id', $orderId
            )->addFilter(
                'ship_zsim', 1,'neq'
            )->addFilter(
                'is_chirashi', 1,'neq'
            )->create();
        }
        $shimentCollection = $this->shipmentRepository->getList($criterial);
        if($shimentCollection->getTotalCount()){
            foreach($shimentCollection->getItems() as $shipment)
            {
                $shipment->setShipmentStatus($shipmentStatus);
                if(array_key_exists('force_payment_status', $dataStatus)){
                    if($paymentStatus){
                        $shipment->setPaymentStatus($paymentStatus);
                    }
                }else {
                    if(!$shipment->getData('grand_total') || $shipment->getData('grand_total')== $shipment->getData('base_shopping_point_amount')){
                        $shipment->setData('payment_status','');
                    }else{
                        $shipment->setData('payment_status', $paymentStatus);
                    }
                }
                try{
                    $shipment->save();
                }catch(\Exception $e){
                    $this->logger->info($e->getMessage());
                }
            }
        }
    }
}