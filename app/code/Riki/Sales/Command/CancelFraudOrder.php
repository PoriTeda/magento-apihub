<?php

namespace Riki\Sales\Command;

use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Riki\BasicSetup\Helper\Data;
use Riki\Sales\Helper\Email;
use Riki\Sales\Model\ResourceModel\Order\OrderStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CancelFraudOrder
 */
class CancelFraudOrder extends Command
{
    const CSV_FILE = 'csv_file';

    const NAME = 'riki:sales:cancel-fraud-order';

    /**
     * @var State
     */
    private $appState;
    /**
     * @var DirectoryList
     */
    private $directoryList;
    /**
     * @var Data
     */
    private $basicHelper;
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var Email
     */
    private $emailHelper;


    /**
     * CancelFraudOrder constructor.
     * @param DirectoryList $directoryList
     * @param ObjectManagerInterface $objectManager
     * @param Data $basicHelper
     * @param State $appState
     * @param string|null $name
     */
    public function __construct(
        DirectoryList $directoryList,
        ObjectManagerInterface $objectManager,
        Data $basicHelper,
        State $appState,
        string $name = null)
    {
        parent::__construct($name);
        $this->directoryList = $directoryList;
        $this->objectManager = $objectManager;
        $this->basicHelper = $basicHelper;
        $this->appState = $appState;
    }

    public function doInject()
    {
        $this->orderFactory = $this->objectManager->get(Order::class . 'Factory');
        $this->emailHelper = $this->objectManager->get(Email::class);
    }

    /**
     * @param array $datas
     * @param InputInterface|null $input
     * @param OutputInterface|null $output
     */
    public function executeUpdate(array $datas, InputInterface $input = null, OutputInterface $output = null)
    {
        foreach ($datas as $_data) {
            $orderNumber = $_data[0];
            /** @var Order $orderObject */
            $orderObject = $this->orderFactory->create()->loadByIncrementId($orderNumber);
            if ($orderObject) {
                // process order
                if ($orderObject->getState() == OrderStatus::STATUS_ORDER_CANCELED || !$orderObject->canCancel()) {
                    $output->writeln("Order: $orderNumber is already canceled.");
                } else {
                    try {
                        if ((bool)$orderObject->cancel()) {
                            $orderObject->addStatusToHistory(
                                OrderStatus::STATUS_ORDER_CANCELED,
                                __('Canceled Order Fraud by Order fixer'),
                                false
                            );
                            $message = "Order Number #$orderNumber has been updated successfully.";
                            $orderObject->save();
                            $output->writeln($message);
                            $receiverEmail = $orderObject->getCustomerEmail();
                            $shippingDescription = $orderObject->getShippingDescription();
                            $this->appState->emulateAreaCode(
                                Area::AREA_ADMINHTML,
                                [$this->emailHelper, 'sendMailCancelFraudOrder'],
                                [$shippingDescription, $receiverEmail]
                            );
                        } else {
                            $message = 'Order #' . $orderNumber . ' has canceled failed';
                            $output->writeln($message);
                        }
                    } catch (Exception $e) {
                        $message = "Have an error when update order Number #{$orderNumber} | [{$e->getMessage()}]";
                        $output->writeln($message);
                    }
                }
            } else {
                $message = "Order Number #$orderNumber does not exist.";
                $output->writeln($message);
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $options = [
            new InputArgument(
                self::CSV_FILE,
                InputArgument::REQUIRED,
                'CSV file to import'
            )
        ];
        $this->setName(self::NAME);
        $this->setDescription('Cancel order via csv file');
        $this->setDefinition($options);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     * @throws FileSystemException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $csvFileName = $input->getArgument(self::CSV_FILE);
        $path = $this->directoryList->getPath(DirectoryList::VAR_DIR);
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
            $output->writeln("Start processing data");
            $this->appState = $this->objectManager->get(State::class);
            try {
                $this->appState->emulateAreaCode(
                    Area::AREA_FRONTEND,
                    [$this, 'doInject'],
                    []
                );
            } catch (Exception $e) {
                $message = "Something went wrong when processing";
                $output->writeln($message);
            }
            $this->appState->emulateAreaCode(
                Area::AREA_FRONTEND,
                [$this, 'executeUpdate'],
                [$datas, $input, $output]
            );
        }
    }
}
