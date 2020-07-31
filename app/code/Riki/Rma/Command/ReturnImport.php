<?php
namespace Riki\Rma\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Riki\Rma\Api\Data\Rma\ReturnStatusInterface;
use Riki\Rma\Api\Data\Rma\RefundStatusInterface;

class ReturnImport extends Command
{
    const FILE_NAME = 'file_name';

    protected $_readerCSV;

    protected $_objectManager;

    protected $_storeId = 1;

    protected $_coreRegistry ;

    protected $_orderId;

    protected $_rmaId;

    protected $_rmaDataMapper;

    protected $_rmaCreate;

    protected $_rma;
    /**
     * @var \Magento\Rma\Model\Rma\Status\History
     */
    protected $_history;

    protected $_state;

    /**
     * @var \Magento\Sales\Model\Order
     */
    protected $_modelOrder;
    /**
     * @var \Riki\Rma\Api\RmaRepositoryInterface
     */
    protected $_rmaRepository;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $_searchHelper;

    /**
     * @var \Riki\Rma\Helper\Refund
     */
    protected $_refundHelper;
    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $_dataHelper;
    /**
     * @var \Riki\Framework\Helper\Datetime
     */
    protected $_datetimeHelper;
    /**
     * @var \Riki\Rma\Api\ItemRepositoryInterface
     */
    protected $_rmaItemRepository;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_varDirectory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $_time
     */
    protected $_time;

    protected $_current_order;

    protected $_output;

    protected $_reasonRepository;

    protected $allOrderObject = [];

    protected $allReasonObject = [];

    protected $_reason;

    protected $_sourceStatus;

    protected $_totalReturnOrders = [];

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resourceConnection;

    /**
     * @var \Riki\Rma\Model\RmaManagement
     */
    protected $rmaManagement;

    public function __construct(
        \Magento\Framework\File\Csv $reader,
        \Magento\Framework\Registry $registry,
        \Magento\Rma\Model\Rma\RmaDataMapper $rmaDataMapper,
        \Magento\Rma\Model\Rma\Create $rmaCreate,
        \Magento\Sales\Model\Order $order,
        \Magento\Rma\Model\Rma\Status\History $history,
        \Magento\Framework\Filesystem $filesystem,
        \Riki\Rma\Api\ItemRepositoryInterface $rmaItemRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Zend_Validate_Date $dateValidator,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        parent::__construct();
        $this->_readerCSV     = $reader;
        $this->_coreRegistry  = $registry;
        $this->_rmaDataMapper = $rmaDataMapper;
        $this->_rmaCreate     = $rmaCreate;
        $this->_modelOrder    = $order;
        $this->_history       = $history;
        $this->_rmaItemRepository = $rmaItemRepository;
        $this->_time = $timezoneInterface;
        $this->dateValidator = $dateValidator;
        $this->dateValidator->setFormat('Y/m/d');
        $this->_varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->_resourceConnection = $resourceConnection;
    }

    /**
     * Set param name for CLI
     */
    protected function configure()
    {
        $options = [
            new InputArgument(
                self::FILE_NAME,
                InputArgument::OPTIONAL,
                'Name of file to import'
            )
        ];

        $this->setName('riki:import-return')
            ->setDescription('A cli import return without good')
            ->setDefinition($options);
        parent::configure();
    }


    /**
     * Load object
     *
     * @return \Magento\Framework\App\ObjectManager
     */
    public function loadObject()
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_objectManager->get('Magento\Framework\App\State')->setAreaCode('adminhtml');
        $this->_rma             = $this->_objectManager->get('Magento\Rma\Model\Rma');
        $this->_searchHelper    = $this->_objectManager->get('Riki\Framework\Helper\Search');
        $this->_rmaRepository   = $this->_objectManager->get('Riki\Rma\Api\RmaRepositoryInterface');
        $this->_datetimeHelper  = $this->_objectManager->get('Riki\Framework\Helper\Datetime');
        $this->_dataHelper      = $this->_objectManager->get('Riki\Rma\Helper\Data');
        $this->_refundHelper    = $this->_objectManager->get('Riki\Rma\Helper\Refund');
        $this->_reasonRepository= $this->_objectManager->get('Riki\Rma\Api\ReasonRepositoryInterface');
        $this->_sourceStatus    = $this->_objectManager->get('Magento\Rma\Model\Rma\Source\Status');
        $this->rmaManagement = $this->_objectManager->get(\Riki\Rma\Model\RmaManagement::class);

        return $this->_objectManager;
    }

    /**
     * Initialize model
     *
     * @param string $requestParam
     * @return \Magento\Rma\Model\Rma
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _initModel()
    {
        /** @var $model \Magento\Rma\Model\Rma */
        $model = $this->_rma;
        $model->setStoreId($this->_storeId);

        if ($this->_orderId) {
            /** @var $order \Magento\Sales\Model\Order */
            $order = $this->_modelOrder->loadByIncrementIdAndStoreId($this->_orderId, $this->_storeId);
            if (!$order->getId()) {
                $this->_output->writeln("--------------------------------------------------------------------------------------");
                $this->_output->writeln("This is the wrong RMA order ID. \n");
            }
            $this->_current_order = $order;
            $this->_orderId = $order->getId();
        }
        return $model;
    }

    /**
     * Process additional RMA information (like comment, customer notification etc)
     *
     * @param array $saveRequest
     * @param \Magento\Rma\Model\Rma $rma
     * @return \Magento\Rma\Controller\Adminhtml\Rma
     */
    public function _processNewRmaAdditionalInfo(array $saveRequest, \Magento\Rma\Model\Rma $rma)
    {
        /** @var $statusHistory \Magento\Rma\Model\Rma\Status\History */
        $systemComment = $this->_history;
        $systemComment->setRmaEntityId($rma->getEntityId());
        $systemComment->saveSystemComment();
        if (!empty($saveRequest['comment']['comment'])) {
            $visible = isset($saveRequest['comment']['is_visible_on_front']);
            /** @var $statusHistory \Magento\Rma\Model\Rma\Status\History */
            $customComment = $this->_history;
            $customComment->setRmaEntityId($rma->getEntityId());
            $customComment->saveComment($saveRequest['comment']['comment'], $visible, true);
        }
        return $this;
    }

    /**
     * convert data import
     *
     * @param $data
     * @return array
     */
    public function convertDataImport($data)
    {
        $dataImport = [];
        $dataImport['order_increment_id'] = isset($data['order_increment_id']) ? $data['order_increment_id'] : '';
        $dataImport['returned_date']      = isset($data['returned_date']) ? $data['returned_date'] : '';
        $dataImport['refund_method']      = isset($data['refund_method']) ? $data['refund_method'] : '';
        $dataImport['reason_id']          = isset($data['reason_code']) ? $data['reason_code'] : '';
        $dataImport['is_without_goods']   = isset($data['is_without_goods']) ? $data['is_without_goods'] : '';
        $dataImport['total_return_amount_adj']          = isset($data['total_return_amount_adj']) ? $data['total_return_amount_adj'] : '';
        $dataImport['total_return_amount_adjusted']     = 0;
        return $dataImport;
    }


    /**
     * @param $model
     * @param $dataImport
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function createNewReturn($model, $dataImport)
    {
        $saveRequest['is_without_goods']                = 1;
        $saveRequest['comment']                         = ['comment'=>null];
        $saveRequest['refund_allowed']                  = 1;
        $model->setData(
            $this->_rmaDataMapper->prepareNewRmaInstanceData(
                $saveRequest,
                $this->_current_order
            )
        );

        $model->setReturnDateCalendar($dataImport['returned_date']);
        $model->setOrigData('returned_date', $dataImport['returned_date']); // by pass validate
        $model->setReturnedDate($dataImport['returned_date']);
        $model->setReasonId($dataImport['reason_id']);
        $model->setIsWithoutGoods(1);
        $model->setRefundAllowed(1);
        $model->setRefundMethod($dataImport['refund_method']);
        $model->setTotalReturnAmountAdj($dataImport['total_return_amount_adj']);
        $model->setTotalReturnAmountAdjusted($dataImport['total_return_amount_adjusted']);

        if (!$model->saveRma($saveRequest)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('We can\'t save this RMA.'));
        }

        $this->_processNewRmaAdditionalInfo($saveRequest, $model);

        return $model;
    }

    /**
     * Save and send for approval
     *
     * @deprecated
     *
     * @param \Magento\Rma\Model\Rma $currentReturn
     */
    public function saveReturnsRequestAccept($currentReturn, $status)
    {
        $ids   = [$currentReturn->getId()];
        $items = $this->_searchHelper
            ->getByEntityId($ids)
            ->getAll()
            ->execute($this->_rmaRepository);

        /** @var \Magento\Rma\Model\Rma $item */
        foreach ($items as $item) {
            $item->setData('return_status', $status);
            $this->_rmaRepository->save($item);
        }
    }


    /**
     * @deprecated
     *
     * @param $currentReturn
     */
    public function saveReturnsApproveCompleted($currentReturn)
    {
        $ids   = [$currentReturn->getId()];
        $items = $this->_searchHelper
            ->getByEntityId($ids)
            ->getAll()
            ->execute($this->_rmaRepository);

        /** @var \Magento\Rma\Model\Rma $item */
        foreach ($items as $item) {
            $item->setStatus(\Magento\Rma\Model\Rma\Source\Status::STATE_PROCESSED_CLOSED);
            $item->setData('return_status', ReturnStatusInterface::COMPLETED);
            $item->setData('is_exported_sap', 1);
            if ($item->getData('refund_allowed') && $item->getData('total_return_amount_adjusted')) {
                if (is_null($item->getData('refund_method'))) {
                    $refundMethods = $this->_refundHelper->getRefundMethodsByPaymentMethod(
                        $this->_dataHelper->getRmaOrderPaymentMethodCode($item),
                        $item
                    );
                    $item->setData('refund_method', key($refundMethods));
                }
                $item->setData('refund_status', RefundStatusInterface::WAITING_APPROVAL);
            }
            $item->setData('return_approval_date', $this->_datetimeHelper->toDb());
            $this->_rmaRepository->save($item);

            foreach ($this->_dataHelper->getRmaItems($item) as $rmaItem) {
                $rmaItem->setData('qty_authorized', $rmaItem->getQtyRequested());
                $rmaItem->setData('qty_approved', $rmaItem->getQtyRequested());
                $rmaItem->setData('qty_returned', $rmaItem->getQtyRequested());
                $rmaItem->setData('status', \Magento\Rma\Model\Rma\Source\Status::STATE_APPROVED);
                $this->_rmaItemRepository->save($rmaItem);
            }
        }
    }

    /**
     * Remove BOM from a file
     *
     * @param string $sourceFile
     * @return $this
     */
    public function removeBom($sourceFile)
    {
        $sourceFile = str_replace('var/', '', $sourceFile);
        $string = $this->_varDirectory->readFile($this->_varDirectory->getRelativePath($sourceFile));
        if ($string !== false && substr($string, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
            $string = substr($string, 3);
            $this->_varDirectory->writeFile($this->_varDirectory->getRelativePath($sourceFile), $string);
        }
        return $this;
    }

    /**
     *
     * @param array $orderIds
     * @return array
     */
    public function getAllOrderId($orderIds = [])
    {
        if (!empty($orderIds)) {
            $orders = $this->_modelOrder->getCollection()
                ->addAttributeToFilter('increment_id', ['in' => $orderIds]);

            return $orders;
        }
        return null;
    }

    /**
     * @param $ids
     * @return mixed
     */
    public function getListReasonId()
    {
        $result = $this->_searchHelper
            ->getAll()
            ->execute($this->_reasonRepository);
        return $result;
    }

    /**
     * Get all Total return amount
     *
     * @param $arrOrderIncrementId
     * @return array
     */
    public function getTotalReturnAmount($arrOrderIncrementId)
    {
        $connection = $this->_resourceConnection->getConnection();
        $table      = $connection->getTableName('magento_rma');
        $orderId    = implode(',', $arrOrderIncrementId);
        $sql = "SELECT order_increment_id,SUM(total_return_amount_adj) as total_return_amount_adj  FROM $table
                WHERE order_increment_id IN ($orderId) GROUP BY order_increment_id
                ";
        $data = $connection->fetchAll($sql);
        if ($data) {
            foreach ($data as $item) {
                if (isset($this->_totalReturnOrders[$item['order_increment_id']])) {
                    $this->_totalReturnOrders[$item['order_increment_id']] += $item['total_return_amount_adj'];
                } else {
                    $this->_totalReturnOrders[$item['order_increment_id']] = ($item['total_return_amount_adj'] !=null) ? $item['total_return_amount_adj'] : 0;
                }
            }
        }
        return $this->_totalReturnOrders;
    }


    /**
     * @param $fileName
     * @return array
     * @throws \Exception
     */
    public function prepareData($fileName)
    {
        $dataResult = [];
        $dataRow = [];
        $this->removeBom($fileName);
        $dataCsv = $this->_readerCSV->getData($fileName);
        $orderIds = [];
        $reasonIds = [];
        foreach ($dataCsv as $key => $value) {
            if ($key == 0) {
                continue;
            }
            foreach ($value as $k => $v) {
                if (isset($dataCsv[0][$k])) {
                    $keyColum = str_replace('"', '', $dataCsv[0][$k]);
                    $dataRow[trim($keyColum)] = $v;
                }
            }

            if ($dataRow['order_increment_id']) {
                $orderIds[] = trim($dataRow['order_increment_id']);
            }
            if ($dataRow['reason_code']) {
                $reasonIds[trim($dataRow['reason_code'])] = trim($dataRow['reason_code']);
            }

            $dataResult[] = $dataRow;
        }

        $allOrderObject = $this->getAllOrderId($orderIds);
        if ($allOrderObject->getSize()>0) {
            foreach ($allOrderObject->getItems() as $order) {
                $this->allOrderObject[$order->getIncrementId()] = $order;
            }
        }

        $this->getTotalReturnAmount($orderIds);

        $reasonObject = $this->getListReasonId($reasonIds);
        foreach ($reasonObject as $item) {
            if ($item->getCode() !='') {
                $this->allReasonObject[$item->getCode()] = $item;
            }
        }

        return $dataResult;
    }


    public function validateData($dataImport, $row)
    {
        $data = [
            'error' => [],
            'dataImport' => $dataImport
        ];

        if (isset($dataImport['is_without_goods']) && !in_array($dataImport['is_without_goods'],[0,1])){
            $data['error'][] = "\t Invalid value for is_without_goods, must be 0 or 1";
        }
        if ($dataImport['order_increment_id'] == null) {
            $data['error'][] = "\t Order increment id is not empty";
        } elseif (!isset($this->allOrderObject[trim($dataImport['order_increment_id'])])) {
            $data['error'][] = "\t Order increment id is invalid " ;
        }

        if ($dataImport['returned_date'] == null) {
            $data['error'][] = "\t Current date is not empty";
        } else {
            $value = trim($dataImport['returned_date']);
            $value = str_replace('/', '-', $value);
            $re1 = '((?:2|1)\\d{3}(?:-|\\/)(?:(?:0[1-9])|(?:1[0-2]))(?:-|\\/)(?:(?:0[1-9])|(?:[1-2][0-9])|(?:3[0-1]))(?:T|\\s)(?:(?:[0-1][0-9])|(?:2[0-3])):(?:[0-5][0-9]):(?:[0-5][0-9]))';    # Time Stamp 1

            $now = $this->_time->date()->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
            $value =  $this->_time->date($value)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');

            if ($c = preg_match_all("/" . $re1 . "/is", $value, $matches)) {
                $dataImport['returned_date'] = $value;
                if (strtotime($dataImport['returned_date']) >  strtotime($now)) {
                    $data['error'][] = "\t Refund date is invalid" ;
                }
            } else {
                $data['error'][] = "\t Refund date is invalid" ;
            }
        }

        if ($dataImport['reason_id'] == null) {
            $data['error'][] = "\t Reason id is not null ";
        } elseif (!isset($this->allReasonObject[trim($dataImport['reason_id'])])) {
            $data['error'][] = "\t Reason id is not valid  ";
        } elseif (isset($this->allReasonObject[trim($dataImport['reason_id'])])) {
            $reason = $this->allReasonObject[trim($dataImport['reason_id'])];
            $dataImport['reason_id'] = $reason->getId();
        }

        if ($dataImport['total_return_amount_adj'] == null) {
            $data['error'][] = "\t Total return amount adj is not empty";
        } elseif ((int)$dataImport['total_return_amount_adj'] < 0) {
            $data['error'][] = "\t Total return amount adj is invalid";
        } elseif (isset($this->allOrderObject[trim($dataImport['order_increment_id'])])) {
            $total = (int)$dataImport['total_return_amount_adj'];
            if (isset($this->_totalReturnOrders[$dataImport['order_increment_id']])) {
                $total += $this->_totalReturnOrders[$dataImport['order_increment_id']];
            }
            $currentOder = $this->allOrderObject[trim($dataImport['order_increment_id'])];
            $grandTotal = $currentOder->getGrandTotal();
            if ($total>$grandTotal) {
                $data['error'][] = "\t Total return amount adj is invalid.";
            } else {
                $dataImport['total_return_amount_adjusted'] = (int)$dataImport['total_return_amount_adj'];
            }
        }

        //check payment method
        if ($dataImport['refund_method'] != null) {
            if (isset($this->allOrderObject[trim($dataImport['order_increment_id'])])) {
                $order = $this->allOrderObject[trim($dataImport['order_increment_id'])];
                $paymentMethod = $order->getPayment()->getMethod();
                if ($paymentMethod) {
                    $arrMethod  = $this->_refundHelper->getRefundMethodsByPaymentMethod($paymentMethod);
                    if (!isset($arrMethod[$dataImport['refund_method']])) {
                        $data['error'][] = "\t Return method is invalid.";
                    }
                } else {
                    $data['error'][] = "\t Return method is invalid.";
                }
            } else {
                $data['error'][] = "\t Return method is invalid.";
            }
        } else {
            $data['error'][] = "\t Return method is null.";
        }

        $data['dataImport'] = $dataImport;

        return $data;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->loadObject();
        $this->_output = $output;

        $fileName = $input->getArgument(self::FILE_NAME);
        if ($fileName != "") {
            $dataResult = $this->prepareData($fileName);

            $row = 2;
            foreach ($dataResult as $data) {
                // convert Data
                $dataConvert = $this->convertDataImport($data);

                $result = $this->validateData($dataConvert, $row);
                $dataImport = $result['dataImport'];
                $errors     = $result['error'];

                if (count($errors) > 0) {
                    $output->writeln("\n------------------------------------------------------------------------------------");
                    $output->writeln("[Row $row] Validate error!\n");
                    $output->writeln($errors);
                } else {
                    $this->_orderId = $dataImport['order_increment_id'];
                    $this->_current_order = null;

                    /** @var $model \Magento\Rma\Model\Rma */
                    $model = $this->_initModel();
                    $model = $this->createNewReturn($model, $dataImport);
                    if ($model instanceof \Magento\Rma\Model\Rma
                        && $model->getEntityId()
                    ) {
                        try {
                            $this->rmaManagement->acceptRequest($model->getEntityId());

                            $this->rmaManagement->approveRequest($model->getEntityId());

                            $this->rmaManagement->approve($model->getEntityId());
                        } catch (\Exception $e) {
                            $output->writeln($e->getTraceAsString());
                        }

                        $returnId = $model->getEntityId();
                        $orderId  = $model->getOrderIncrementId();
                        $output->writeln("--------------------------------------------------------------------------------------");
                        $output->writeln("[Row $row] Create return success with order Id ( $orderId  - $returnId) \n");
                    }
                }

                $row++;
            }
        }
    }
}
