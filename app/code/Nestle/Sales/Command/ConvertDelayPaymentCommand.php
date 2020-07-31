<?php

namespace Nestle\Sales\Command;

use Symfony\Component\Console\Command\Command;
use Bluecom\Paygent\Exception\PaygentCaptureException;
use Magento\Framework\DB\Transaction;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use NestleCommand\NestleCommandAbstract;
use Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus;
use Riki\Framework\Helper\Logger\LoggerBuilder;
use Riki\MessageQueue\Model\ResourceModel\QueueLock;
use Riki\Sales\Helper\Order;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Riki\Subscription\Exception\DelayPaymentSaveReAuthorizeDataException;
use Riki\SubscriptionCourse\Model\Course\Type;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Riki\Subscription\Model\ResourceModel\Profile;
use Symfony\Component\Console\Input\InputArgument;
use Riki\DelayPayment\Helper\Data;

class ConvertDelayPaymentCommand extends Command
{
    const FILE_NAME = 'file_name';

    const CSV_MIGRATE_TYPE = 'CSV';

    const DATABASE_MIGRATE_TYPE = 'DATABASE';

    const PAYMENT_AGENT_NICOS2 = 'NICOS2';

    const PAYMENT_AGENT_JCB2 = 'JCB2';

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var TransactionFactory
     */
    protected $transactionFactory;

    /**
     * @var \Bluecom\Paygent\Model\PaygentManagement
     */
    protected $paygentManagement;

    /**
     * @var \Riki\Sales\Model\CaptureOrder\Consumer\OrderCaptureAbstract
     */
    protected $orderCaptureAbstract;

    /**
     * @var QueueLock
     */
    protected $queueLock;

    /**
     * @var \Bluecom\Paygent\Cron\Authorisation
     */
    protected $authorisation;

    /**
     * @var \Bluecom\Paygent\Helper\Data
     */
    protected $paygentHelper;

    /**
     * @var \Magento\Framework\File\Csv $readerCSV
     */
    protected $readerCSV;

    /**
     * @var \Riki\SubscriptionCourse\Command\Import $importHelper
     */
    protected $importHelper;

    /**
     * @var \Riki\Sales\Model\CaptureOrder\Consumer\OrderCapture $orderCapture
     */
    protected $orderCapture;

    /**
     * @var $logger
     */
    protected $logger;

    /**
     * @var LoggerBuilder
     */
    protected $loggerBuilder;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $orderRepository;

    public function __construct(
        OrderCollectionFactory $orderCollectionFactory,
        TransactionFactory $transactionFactory,
        \Bluecom\Paygent\Model\PaygentManagement $paygentManagement,
        \Riki\Sales\Model\CaptureOrder\Consumer\OrderCapture $orderCapture,
        \Riki\Sales\Model\CaptureOrder\Consumer\OrderCaptureAbstract $orderCaptureAbstract,
        QueueLock $queueLock,
        \Bluecom\Paygent\Cron\Authorisation $authorisation,
        \Bluecom\Paygent\Helper\Data $paygentHelper,
        \Magento\Framework\File\Csv $csv,
        \Riki\SubscriptionCourse\Command\Import $import,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        LoggerBuilder $loggerBuilder
    )
    {
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->transactionFactory = $transactionFactory;
        $this->paygentManagement = $paygentManagement;
        $this->orderCapture = $orderCapture;
        $this->orderCaptureAbstract = $orderCaptureAbstract;
        $this->queueLock = $queueLock;
        $this->authorisation = $authorisation;
        $this->paygentHelper = $paygentHelper;
        $this->readerCSV = $csv;
        $this->importHelper = $import;
        $this->orderRepository = $orderRepository;
        $this->loggerBuilder = $loggerBuilder;
        parent::__construct();
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

        $this->setName('nestle:backend-operation:convertdelaytonormalorder')
            ->setDescription('A cli convert delay to normal order')
            ->setDefinition($options);

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        // Set Area Code
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $appState = $objectManager->get(\Magento\Framework\App\State::class);
        $appState->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);

        $fileName = $input->getArgument(self::FILE_NAME);
        // if has file => convert from file else fetch from db
        if ($fileName != "") {
            $this->processFromCsvFile($output, $fileName);
        } else {
            $this->processFromDataBase();
        }
    }

    public function processFromCsvFile(OutputInterface $output, $fileName)
    {
        try {
            $dataCsv = $this->readerCSV->getData($fileName);

            if (!empty($dataCsv[0][0])) {
                foreach ($dataCsv as $key => $data) {
                    if ($key == 0) {
                        continue;
                    }
                    // convert Data
                    $dataImport['increment_id'] = $this->importHelper->checkColumExit('0', $data);

                    try {
                        $delayOrders = $this->getDelayOrderToConvertByIncrementId($dataImport['increment_id']);
                        if ($delayOrders->getSize() > 0) {
                            //migration
                            $output->writeln("\n---------------------------------------");
                            $output->writeln("\nSTART MIGRATION");
                            $this->migration($delayOrders, self::CSV_MIGRATE_TYPE);
                            $output->writeln(
                                "Row " . ($key + 1) . " Increment id: " . $dataImport['increment_id'] . " migrated successfully!\n"
                            );
                        } else {
                            $output->writeln("\n---------------------------------------");
                            $output->writeln(
                                "Row. " . ($key + 1) . " Validate error!"
                            );
                            $message = "Not exist increment id satisfy condition " . $dataImport['increment_id'];
                            $output->writeln($message);
                            $this->getLoggerMigration()->error($message);
                        }
                    } catch (\Exception $e) {
                        $output->writeln(
                            $e->getMessage()
                        );
                    }
                    unset($dataCsv[$key]);
                }
            } else {
                $output->writeln('No input data');
            }
        } catch (\Exception $e) {
            $output->writeln(
                $e->getMessage()
            );
        }
    }

    public function processFromDataBase()
    {
        $delayOrders = $this->getDelayOrdersToConvert();
        //migration
        $this->migration($delayOrders, self::DATABASE_MIGRATE_TYPE);
    }

    public function migration($delayOrders, $migrateType)
    {
        foreach ($delayOrders->getData() as $delayOrder) {
            // migrate orders that has subscription order time > last order time is delay payment
            $this->getLoggerMigration()->info('START MIGRATION: ' . $delayOrder['increment_id']);
            switch ($migrateType) {
                case self::DATABASE_MIGRATE_TYPE:
                    if (!empty($delayOrder['last_order_time_is_delay_payment']) && $delayOrder['last_order_time_is_delay_payment'] > 0 && ($delayOrder['subscription_order_time'] > $delayOrder['last_order_time_is_delay_payment'])) {
                        $this->processMigration($delayOrder);
                    }
                    break;
                case self::CSV_MIGRATE_TYPE:
                    $this->processMigration($delayOrder);
                    break;
            }
        }
    }

    public function processMigration($delayOrder)
    {
        $delayOrder = $this->orderRepository->get($delayOrder['entity_id']);
        $this->getLoggerMigration()->info('Increment id prepare for migration: ' . $delayOrder->getIncrementId());
        if ($delayOrder->getPaymentAgent() == self::PAYMENT_AGENT_NICOS2) {
            $delayOrder->setPaymentAgent(Data::PAYMENT_AGENT_NICOS);
            $this->getLoggerMigration()->info('Changed payment_agent from NICOS2 to NICOS successfully!');
        } else if ($delayOrder->getPaymentAgent() == self::PAYMENT_AGENT_JCB2) {
            $delayOrder->setPaymentAgent(Data::PAYMENT_AGENT_JCB);
            $this->getLoggerMigration()->info('Changed payment_agent from JCB2 to JCB successfully!');
        }

        //execute reauthorize
        try {
            if ($this->reAuthorize($delayOrder)) {
                $this->captureOrder($delayOrder);
            };
            //set riki_type after capture order to keep flow work as normal
            $delayOrder->setRikiType(Order::RIKI_TYPE_SUBSCRIPTION);
            $this->getLoggerMigration()->info('Changed riki_type to subscription successfully!');

            /** @var Transaction $transaction */
            $transaction = $this->transactionFactory->create();
            $transaction->addObject($delayOrder)->save();
            $this->getLoggerMigration()->info('Convert delay order to normal order successfully!');
            $this->getLoggerMigration()->info('END MIGRATION');
        } catch (DelayPaymentSaveReAuthorizeDataException $e) {
            $message = __(
                'SAVE REAUTHORIZE DATA DELAY PAYMENT FAILED : #%1 : %2',
                $delayOrder->getIncrementId(),
                $e->getMessage()
            );
            $this->getLoggerMigration()->error($message);
            throw new LocalizedException($message);
        } catch (PaygentCaptureException $e) {
            $this->getLoggerMigration()->error(__('CAPTURE FAILED : #%1 : %2', $delayOrder->getIncrementId(), $e->getMessage()));
            $this->orderCapture->captureFailureCallback($delayOrder);
        } catch (\Exception $e) {
            $this->getLoggerMigration()->error('MIGRATION FAILED  ' . $e->getMessage());
        }
    }

    public function getDelayOrderToConvertByIncrementId($incrementId)
    {
        $delayOrders = $this->orderCollectionFactory->create();
        $delayOrders->addFieldToFilter('increment_id', $incrementId)
            ->addFieldToFilter('payment_status', ['neq' => \Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus::PAYMENT_COLLECTED])
            ->addFieldToFilter('riki_type', Type::TYPE_ORDER_DELAY_PAYMENT)
            ->addFieldToFilter('state', ['neq' => OrderStatus::STATUS_ORDER_CANCELED])
            ->addFieldToFilter('main_table.status', ['neq' => OrderStatus::STATUS_ORDER_CAPTURE_FAILED]);

        return $delayOrders;
    }

    public function getDelayOrdersToConvert()
    {
        $delayOrders = $this->orderCollectionFactory->create();
        $delayOrders->getSelect()
            ->join(['sp' => $delayOrders->getResource()->getTable('subscription_profile')],
                'main_table.subscription_profile_id = sp.profile_id')
            ->join(['sc' => $delayOrders->getResource()->getTable('subscription_course')],
                'sp.course_id = sc.course_id and main_table.subscription_order_time > sc.last_order_time_is_delay_payment', [
                    'last_order_time_is_delay_payment' => 'sc.last_order_time_is_delay_payment'
                ]);
        $delayOrders
            ->addFieldToFilter('payment_status', ['neq' => PaymentStatus::PAYMENT_COLLECTED])
            ->addFieldToFilter('riki_type', Type::TYPE_ORDER_DELAY_PAYMENT)
            ->addFieldToFilter('state', ['neq' => OrderStatus::STATUS_ORDER_CANCELED])
            ->addFieldToFilter('main_table.status', ['neq' => OrderStatus::STATUS_ORDER_CAPTURE_FAILED])
            ->addFieldToFilter('sc.last_order_time_is_delay_payment', ['gt' => 0]);
        return $delayOrders;
    }

    /**
     * @return bool
     * @throws LocalizedException
     */
    public function reAuthorize($order)
    {
        $canCapture = false;
        $payment = $order->getPayment();
        $unclosedAuthorizeTransactions = $this->authorisation->getUnclosedAuthorizeTransactionsByPayment($payment->getEntityId());
        $orderStatus = $order->getStatus();

        // if doesn't has unclosed transaction => reauthorize
        if (empty($unclosedAuthorizeTransactions)) {
            $this->getLoggerMigration()->info(__('Start reauthorize the order #%1', $order->getIncrementId()));
            list($status, $result, $paymentObject) = $this->paygentManagement->authorize($order);
            // if status is failed => reset order status
            if (!$status) {
                $errorDetail = $paymentObject->getResponseDetail() ?: 'Others';
                $errorMessage = $this->paygentManagement->getPaygentModel()
                    ->getErrorMessageByErrorCode($paymentObject->getResponseDetail());

                $this->getLoggerMigration()->critical(__('Order %1 cannot authorized successfully due to issue from Paygent: %2',
                    $order->getIncrementId(),
                    $errorMessage));

                $order->setPaymentErrorCode($errorDetail);
                if ($orderStatus == OrderStatus::STATUS_ORDER_SHIPPED_ALL) {
                    $order->setPaymentStatus(
                        \Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus::PAYMENT_CAPTURE_FAILED
                    );
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                    $order->setStatus(OrderStatus::STATUS_ORDER_CAPTURE_FAILED);
                    $order->addStatusHistoryComment(
                        __('Authorize failed: %1', $errorMessage),
                        OrderStatus::STATUS_ORDER_CAPTURE_FAILED
                    );
                } else {
                    $order->setPaymentStatus(
                        \Riki\ArReconciliation\Model\ResourceModel\Status\PaymentStatus::PAYMENT_AUTHORIZED_FAILED
                    );
                    $order->setState(\Magento\Sales\Model\Order::STATE_PROCESSING);
                    $order->setStatus(OrderStatus::STATUS_ORDER_PENDING_CC);
                    $order->addStatusHistoryComment(
                        __('Authorize failed: %1', $errorMessage),
                        OrderStatus::STATUS_ORDER_PENDING_CC
                    );
                }

                $this->authorisation->sendAuthorizeFailureMail($order, $errorMessage);
                $this->createTransaction($order);
                $this->getLoggerMigration()->info(__('End reauthorize the order #%1', $order->getIncrementId()));
                return $canCapture;
            }
            // status is true
            $order->setIsNotified(false);
            $order->addStatusHistoryComment(__('Re-authorized successfully.'), false);
            $this->getLoggerMigration()->info(__('Re-authorized successfully order #%1', $order->getIncrementId()));

            //save reference trading id
            $order->setRefTradingId($order->getIncrementId());
            $order->setPaymentStatus(PaymentStatus::PAYMENT_AUTHORIZED);
            // create transaction
            $this->createTransaction($order);
            $canCapture = $this->checkCanCaptureOrder($orderStatus);
            $this->getLoggerMigration()->info(__('End reauthorize the order #%1', $order->getIncrementId()));

            return $canCapture;
        }
        // Has active transaction => Capture order
        $canCapture = $this->checkCanCaptureOrder($orderStatus);

        return $canCapture;
    }

    public function checkCanCaptureOrder($orderStatus)
    {
        switch ($orderStatus) {
            case OrderStatus::STATUS_ORDER_SHIPPED_ALL:
            case OrderStatus::STATUS_ORDER_COMPLETE:
                $canCapture = true;
                break;
            default:
                $canCapture = false;
                break;
        }

        return $canCapture;
    }

    public function captureOrder($order)
    {
        $this->getLoggerMigration()->info(__('Start to capture the order #%1', $order->getIncrementId()));
        if ($order->canInvoice()) {
            $invoice = $this->orderCaptureAbstract->capture($order);
            $this->orderCaptureAbstract->captureSuccessfullyCallback($order, $invoice);
            $this->getLoggerMigration()->info(__('SUCCESS : %1', $order->getIncrementId()));
        } else {
            $this->getLoggerMigration()->info(__('Can not create invoice #%1', $order->getIncrementId()));
        }
        $this->queueLock->deleteLock(\Riki\Sales\Model\CaptureOrder\Consumer\OrderCapture::CAPTURE_ORDER_QUEUE_NAME, $order->getId());
        $this->getLoggerMigration()->info(__('End capture the order #%1', $order->getIncrementId()));
    }

    public function createTransaction($order)
    {
        /** @var Transaction $transaction */
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($order);

        try {
            $transaction->save();
        } catch (\Exception $e) {
            $this->getLoggerMigration()->error(__('Can not create invoice #%1', $order->getIncrementId()));
            $this->getLoggerMigration()->critical($e);
            $this->getLoggerMigration()->addError(__(
                'Reauthorize order #%1 error: %2',
                $order->getIncrementId(),
                $e->getMessage()
            ));
            $this->getLoggerMigration()->critical($e);
        }
    }

    public function getLoggerMigration()
    {
        if (!$this->logger) {
            $this->logger = $this->createLogger('migration' . time());
        }

        return $this->logger;
    }

    /**
     * @param $name
     * @return \Riki\Framework\Helper\Logger\Monolog
     * @throws \Exception
     */
    protected function createLogger($name)
    {
        return $this->loggerBuilder
            ->setName('Riki_Paygent')
            ->setFileName($name . '.log')
            ->pushHandlerByAlias(LoggerBuilder::ALIAS_DATE_HANDLER)
            ->create();
    }
}