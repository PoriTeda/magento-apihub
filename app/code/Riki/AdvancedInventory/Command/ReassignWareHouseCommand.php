<?php
namespace Riki\AdvancedInventory\Command;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

class ReassignWareHouseCommand extends Command
{

    const FILE_NAME = 'file_name';
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $reader;
    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;
    /**
     * @var \Riki\AdvancedInventory\Helper\Assignation
     */
    protected $assignationHelper;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;
    /**
     * @var \Riki\Sales\Helper\Address
     */
    protected $_addressHelper;
    /**
     * @var \Wyomind\AdvancedInventory\Model\Assignation
     */
    protected $assignationModel;
    /**
     * @var \Riki\AdvancedInventory\Model\Assignation
     */
    protected $rikiAssignationModel;
    /**
     * @var \Magento\Sales\Model\Order\ShipmentFactory
     */
    protected $shipmentFactory;
    /**
     * @var \Wyomind\AdvancedInventory\Model\StockFactory
     */
    protected $stockFactory;
    /**
     * @var \Riki\AdvancedInventory\Logger\LoggerReAssign
     */
    protected $loggerReAssign;

    public function __construct(
        \Riki\AdvancedInventory\Model\AssignationFactory $assignationFactory,
        \Magento\Framework\File\Csv $reader,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Sales\Model\Order\ShipmentFactory $shipmentFactory,
        \Wyomind\AdvancedInventory\Model\StockFactory $stockFactory,
        \Riki\AdvancedInventory\Logger\LoggerReAssign $loggerReAssign,
        $name = null
    )
    {
        $this->reader = $reader;
        $this->resourceConnection = $resourceConnection;
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->objectManager = $objectManager;
        $this->appState = $appState;
        $this->shipmentFactory = $shipmentFactory;
        $this->stockFactory = $stockFactory;
        $this->loggerReAssign = $loggerReAssign;
        $this->rikiAssignationModel = $this->appState->emulateAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB, [$assignationFactory, 'create']);
        parent::__construct($name);
    }


    protected function configure()
    {
        $options = [
            new InputArgument(
                self::FILE_NAME,
                InputArgument::REQUIRED,
                'FILE NAME'
            )
        ];
        $this->setName('riki:advancedInventory:reassign')
             ->setDescription('Re-assign ware house')
             ->setDefinition($options);
        parent::configure();
    }
    /**
     * {@inheritdoc}
     *
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        $this->appState->emulateAreaCode(\Magento\Framework\App\Area::AREA_CRONTAB,[$this,'doExecute'],[$input,$output]);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function doExecute(InputInterface $input, OutputInterface $output) {
        $fileName = $input->getArgument(self::FILE_NAME);
        $connectionSales = $this->resourceConnection->getConnection('sales');

        $dataCSV = $this->reader->getData($fileName);
        $errors = 0;
        $success = 0;
        $errorShipment = 0;

        if (is_array($dataCSV)) {
            array_shift($dataCSV);
            foreach ($dataCSV as $key => $value) {
                $line = $key + 1;
                $incrementId = isset($value[0])?$value[0]:null;
                if(is_null($incrementId)) {
                    $output->writeln("\n------------------------------------------------------------------------------------");
                    $output->writeln("[Row $line] Increment order is null.\n");
                    $this->loggerReAssign->addError("[Row $line] Increment order is null.");
                    $errors ++;
                }
                else {
                    $orderModel = $this->orderFactory->create()->loadByIncrementId($incrementId);
                    if ($orderModel->getId()) {
                        $incrementId = $orderModel->getIncrementId();
                        /*group item by address*/
                        $orderModel->setData('re_assign_stock',true);
                        /*Begin*/
                        $stockResource = $this->stockFactory->create()->getResource();
                        $transactionConn = $stockResource->getTransactionConnection();
                        $transactionConn->beginTransaction();
                        try {

                            /*delete assignation stock */
                            $dataBeforeDeleteAssignation = $this->deleteOldAssignation($orderModel);
                            if(sizeof($dataBeforeDeleteAssignation) > 0) {
                                $orderItems = $orderModel->getAllItems();
                                foreach ($orderItems as $item) {
                                    $productId = $item->getProductId();
                                    foreach ($dataBeforeDeleteAssignation as $itemStock) {
                                        if($item->getId() == $itemStock['item_id']) {
                                            $qtyRevert = $itemStock['qty_assigned'];
                                            $placeId =  $itemStock['place_id'];
                                            $sqlRevert = "update advancedinventory_stock set quantity_in_stock = quantity_in_stock + $qtyRevert where product_id = $productId and place_id = $placeId";
                                            $transactionConn->query($sqlRevert);
                                        }
                                    }
                                }
                            }
                            $this->rikiAssignationModel->order = $orderModel;
                            $assignTo = $this->rikiAssignationModel->generateAssignationByOrder($orderModel, true);
                            $shipmentCollection = $orderModel->getShipmentsCollection();
                            if (sizeof($shipmentCollection) == 0) {
                                /*case 1: Order created but shipment not created.*/
                                try {
                                    $this->reAssignStock($orderModel,$assignTo);
                                    $output->writeln("\n------------------------------------------------------------------------------------");
                                    $output->writeln("[Row $line] Order $incrementId re-assigned successfully.\n");
                                    $this->loggerReAssign->addInfo("[Row $line] Order $incrementId re-assigned successfully.");
                                    $success ++;
                                } catch (\Exception $e) {
                                    $output->writeln("\n------------------------------------------------------------------------------------");
                                    $output->writeln("[Row $line]".$e->getMessage());
                                    $output->writeln("[Row $line] Order $incrementId cannot update new assignation warehouse.\n");
                                    $this->loggerReAssign->addError("[Row $line]".$e->getMessage());
                                    $this->loggerReAssign->addError("[Row $line] Order $incrementId cannot update new assignation warehouse.");
                                    $errors ++;
                                }
                            }
                            else {
                                /*case 2: Order created, shipment created but shipment not send to WH yet (shipment status = Created)*/
                                /** @var $shipment \Magento\Sales\Model\Order\Shipment */
                                $connectionSales->beginTransaction();
                                foreach ($shipmentCollection as $shipment) {
                                    $shipmentIncrementId = $shipment->getIncrementId();
                                    $shipmentStatus = $shipment->getShipmentStatus();
                                    if ($shipment->getShipmentStatus() == 'created' and $shipment->getData('warehouse') != 'BIZEX') {
                                        $shipment->setData('warehouse','BIZEX');
                                        try {
                                            $shipment->save();
                                            $output->writeln("\n------------------------------------------------------------------------------------");
                                            $output->writeln("[Row $line] Shipment $shipmentIncrementId updated successfully.\n");
                                            $this->loggerReAssign->addInfo("[Row $line] Shipment $shipmentIncrementId updated successfully.\n");
                                        } catch (\Exception $e) {
                                            $output->writeln("\n------------------------------------------------------------------------------------");
                                            $output->writeln("[Row $line]".$e->getMessage());
                                            $this->loggerReAssign->addError("[Row $line]".$e->getMessage());
                                            $output->writeln("[Row $line] Shipment $shipmentIncrementId cannot update new assignation warehouse.\n");
                                            $this->loggerReAssign->addError("[Row $line] Shipment $shipmentIncrementId cannot update new assignation warehouse.");
                                            $errorShipment ++;
                                            break;
                                        }
                                    } elseif ($shipment->getShipmentStatus() == 'exported' and $shipment->getData('warehouse') != 'BIZEX') {
                                        $shipment->setData('warehouse','BIZEX');
                                        $shipment->setData('is_exported',0);
                                        $shipment->setData('shipment_status',\Riki\Shipment\Model\ResourceModel\Status\Options\Shipment::SHIPMENT_STATUS_CREATED);
                                        try {
                                            $shipment->save();
                                            $output->writeln("\n------------------------------------------------------------------------------------");
                                            $output->writeln("[Row $line] Shipment $shipmentIncrementId updated successfully.\n");
                                            $this->loggerReAssign->addInfo("[Row $line] Shipment $shipmentIncrementId updated successfully.");
                                        } catch (\Exception $e) {
                                            $output->writeln("\n------------------------------------------------------------------------------------");
                                            $output->writeln("[Row $line]".$e->getMessage());
                                            $this->loggerReAssign->addError("[Row $line]".$e->getMessage());
                                            $output->writeln("[Row $line] Shipment $shipmentIncrementId cannot update new assignation warehouse.\n");
                                            $this->loggerReAssign->addError("[Row $line] Shipment $shipmentIncrementId cannot update new assignation warehouse.");
                                            $errorShipment ++;
                                            break;
                                        }
                                    } else {
                                        $output->writeln("\n------------------------------------------------------------------------------------");
                                        $output->writeln("[Row $line] Shipment $shipmentIncrementId cannot re-assign because status is $shipmentStatus .\n");
                                        $this->loggerReAssign->addError("[Row $line] Shipment $shipmentIncrementId cannot re-assign because status is $shipmentStatus.");
                                    }
                                }
                                if ($errorShipment > 0) {
                                    $output->writeln("\n------------------------------------------------------------------------------------");
                                    $output->writeln("[Row $line] Order $incrementId cannot update new assignation warehouse because has one shipment cannot change warehouse.\n");
                                    $this->loggerReAssign->addError("[Row $line] Order $incrementId cannot update new assignation warehouse because has one shipment cannot change warehouse.");
                                    $errors ++;
                                    $connectionSales->rollBack();
                                }
                                else {
                                    try {
                                        $this->reAssignStock($orderModel,$assignTo);
                                        $output->writeln("\n------------------------------------------------------------------------------------");
                                        $output->writeln("[Row $line] Order $incrementId re-assigned successfully.\n");
                                        $this->loggerReAssign->addInfo("[Row $line] Order $incrementId re-assigned successfully.");
                                        $success ++;
                                        $connectionSales->commit();
                                    } catch (\Exception $e) {
                                        $output->writeln("\n------------------------------------------------------------------------------------");
                                        $output->writeln("[Row $line]".$e->getMessage());
                                        $this->loggerReAssign->addError("[Row $line]".$e->getMessage());
                                        $output->writeln("[Row $line] Order $incrementId cannot update new assignation warehouse.\n");
                                        $this->loggerReAssign->addError("[Row $line] Order $incrementId cannot update new assignation warehouse.");
                                        $errors ++;
                                        $connectionSales->rollBack();
                                    }
                                }
                            }
                            $transactionConn->commit();
                        } catch (\Exception $e){
                            $message = $e->getMessage();
                            $transactionConn->rollBack();
                            $output->writeln("\n------------------------------------------------------------------------------------");
                            $output->writeln("[Row $line] Order $incrementId::$message .\n");
                            $this->loggerReAssign->addError("[Row $line] Order $incrementId::$message ");
                            $errors ++;
                        }
                    }
                    else{
                        $output->writeln("\n------------------------------------------------------------------------------------");
                        $output->writeln("[Row $line] Order $incrementId:: Increment order is invalid.\n");
                        $this->loggerReAssign->addError("[Row $line] Order $incrementId:: Increment order is invalid.");
                        $errors ++;
                    }
                }
            }
        } else {
            $output->writeln("\n------------------------------------------------------------------------------------");
            $output->writeln("This CSV file is wrong format");
            $this->loggerReAssign->addError("This CSV file is wrong format");
        }
        $output->writeln("\n------------------------------------------------------------------------------------");
        $output->writeln("$errors orders cannot re-assign");
        $this->loggerReAssign->addInfo("$errors orders cannot re-assign");
        $output->writeln("$success orders was re-assigned successfully");
        $this->loggerReAssign->addInfo("$success orders was re-assigned successfully");

    }
    public function reAssignStock(\Magento\Sales\Model\Order $order,$assignTo) {
        $this->rikiAssignationModel->insertWithUpdateWhStock($order->getId(),$assignTo);
        $orderChannel = $order->getOrderChannel();
        $order->setOrderChannel($orderChannel);
        $order->setAssignation(json_encode($assignTo['inventory']));
        $order->setAssignedTo(2);
        try {
            $order->save();
        } catch (\Exception $e){
            throw $e;
        }
    }

    public function deleteOldAssignation(\Magento\Sales\Model\Order $order) {
        $connection = $this->resourceConnection->getConnection('default');
        $orderItems = $order->getAllItems();
        $orderItemIds = [];
        $data = [];
        foreach ($orderItems as $item) {
            $orderItemIds[] = $item->getId();
        }
        if (sizeof($orderItemIds) > 0) {
            $orderItemIdsString = implode(',',$orderItemIds);
            $sqlSelect = "select * from advancedinventory_assignation where item_id in ($orderItemIdsString)";
            $data = $connection->fetchAll($sqlSelect);
            $sqlDelete = "delete from advancedinventory_assignation where item_id in ($orderItemIdsString)";
            $connection->query($sqlDelete);
        }
        return $data;
    }

    public function revertStockAfterReAssign(\Magento\Sales\Model\Order $order,$dataBeforeDelete) {



        $orderItems = $order->getAllItems();
        foreach ($orderItems as $item) {
            $productId = $item->getProductId();
            foreach ($dataBeforeDelete as $itemStock) {
                if($item->getId() == $itemStock['item_id']) {
                    $qtyRevert = $itemStock['qty_assigned'];
                    $placeId =  $itemStock['place_id'];
                    $sqlRevert = "update advancedinventory_stock set quantity_in_stock = $qtyRevert where product_id = $productId and place_id = $placeId";
//                    $connection->query($sqlRevert);
                }
            }
        }
    }
}