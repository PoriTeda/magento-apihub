<?php
namespace Riki\Rma\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class UpdatePaymentStatus extends Command
{
    const FILE_NAME = 'file_name';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_varDirectory;
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $_readerCSV;
    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected $_orderCollection;
    /**
     * @var \Magento\Rma\Model\ResourceModel\Grid\Collection
     */
    protected $_rmaGridCollection;

    protected $_logger;

    protected $rmaList = [];

    protected $orderList = [];

    protected $updateList = [];

    /**
     *
     * Set param name for CLI
     *
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

        $this->setName('riki:update-rma-payment-status')
            ->setDescription('A cli update rma payment status')
            ->setDefinition($options);
        parent::configure();
    }

    /**
     * @param $fileName
     * @return array
     * @throws \Exception
     */
    public function prepareData($fileName)
    {
        $varDirectory = 'var/';

        $this->removeBom($fileName);

        $dataCsv = $this->_readerCSV->getData(
            $varDirectory.$fileName
        );
        //get all RMA increment_id
        foreach ($dataCsv as $vl) {
            if (!in_array($vl[0], $this->rmaList)) {
                array_push($this->rmaList, $vl[0]);
            }
        }

        if (empty($this->rmaList)) {
            echo "File content is empty.".PHP_EOL;
            $this->_logger->info("File content is empty. File name is ".$fileName);
            return;
        }

        //get order id based on rma increment_id
        $rmaData = $this->_rmaGridCollection->addFieldToFilter(
            'increment_id', [
                'in' => $this->rmaList
            ]
        );

        if($rmaData->getSize()) {
            foreach ($rmaData->getItems() as $rma){
                if(!in_array($rma->getOrderId(), $this->orderList)){
                    array_push($this->orderList, $rma->getOrderId());
                }
            }
        }else {
            echo "All rma are not exists." . PHP_EOL;
            $this->_logger->info("All rma are not exists.");
            return;
        }
    }

    /**
     * @param $sourceFile
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

    public function initCommand()
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_filesystem = $this->_objectManager->create('Magento\Framework\Filesystem');
        $this->_varDirectory = $this->_filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->_readerCSV = $this->_objectManager->create('Magento\Framework\File\Csv');

        $this->_orderCollection = $this->_objectManager->create('Magento\Sales\Model\ResourceModel\Order\Collection');
        $this->_rmaGridCollection = $this->_objectManager->create('Magento\Rma\Model\ResourceModel\Grid\Collection');

        $this->_logger = $this->_objectManager->create(
            'Riki\Framework\Helper\Logger\LoggerBuilder'
        )->setName(
            'RIM1495'
        )->setFileName(
            'RIM1495'
        )->pushHandlerByAlias(
            \Riki\Framework\Helper\Logger\LoggerBuilder::ALIAS_DATE_HANDLER
        )->create();
    }

    /**
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileName    = $input->getArgument(self::FILE_NAME);
        if (empty($fileName)) {
            echo "Please insert your file name".PHP_EOL;
            return;
        }

        $this->initCommand();

        $this->prepareData($fileName);
        if (empty($this->orderList)) {
            echo "File content is empty.".PHP_EOL;
            $this->_logger->info("File content is empty. File name is ".$fileName);
            return;
        }

        $this->getOrderPaymentStatus();
    }

    public function getOrderPaymentStatus()
    {
        //get order data
        $orderData = $this->_orderCollection->addFieldToFilter(
            'entity_id' , [
                'in' => $this->orderList
            ]
        );
        if ($orderData->getSize()) {
            foreach ($orderData->getItems() as $order) {
                if ($order->getStatus() == 'complete') {
                    if ($order->getPaymentStatus() == 'payment_collected') {
                        echo 'Order #'.$order->getIncrementId().' has been collected.'.PHP_EOL;
                        $this->_logger->info('Order #'.$order->getIncrementId().' has been collected.');
                    } else {
                        $order->setPaymentStatus('payment_collected');
                        try {
                            $order->save();
                            echo 'Order #'.$order->getIncrementId().' has been changed to payment_collected.'.PHP_EOL;
                            $this->_logger->info('Order #'.$order->getIncrementId().' has been changed to payment_collected.');
                        } catch (\Exception $e) {
                            echo 'Cannot change payment status for order #'.$order->getIncrementId().PHP_EOL;
                            $this->_logger->info('Cannot change payment status for order #'.$order->getIncrementId());
                            $this->_logger->info('Error: '. $e->getMessage());
                        }
                    }
                    if (!in_array($order->getEntityId(), $this->updateList)) {
                        $this->updateList[$order->getEntityId()] = $order->getPaymentStatus();
                    }
                }
            }
        }
        else {
            echo "All orders are not exists.".PHP_EOL;
            $this->_logger->info("All orders are not exists.");
        }

        $this->updatePaymentStatus();
    }

    public function updatePaymentStatus()
    {
        $this->_rmaGridCollection = $this->_objectManager->create('Magento\Rma\Model\ResourceModel\Grid\Collection');
        //get rma data
        $rmaData = $this->_rmaGridCollection->addFieldToFilter(
            'order_id', [
                'in' => array_keys($this->updateList)
            ]
        );

        if($rmaData->getSize()){
            foreach ($rmaData->getItems() as $rma){
                if ($rma->getPaymentStatus() == 'payment_collected') {
                    echo 'Rma #'.$rma->getIncrementId().' has been collected.'.PHP_EOL;
                    $this->_logger->info('Rma #'.$rma->getIncrementId().' has been collected.');
                } else {
                    $rma->setPaymentStatus('payment_collected');
                    try {
                        $rma->save();
                        echo 'Rma #'.$rma->getIncrementId().' has been changed to payment_collected.'.PHP_EOL;
                        $this->_logger->info('Rma #'.$rma->getIncrementId().' has been changed to payment_collected.');
                    } catch (\Exception $e) {
                        echo 'Cannot change payment status for Rma #'.$rma->getIncrementId().PHP_EOL;
                        $this->_logger->info('Cannot change payment status for Rma #'.$rma->getIncrementId());
                        $this->_logger->info('Error: '. $e->getMessage());
                    }
                }
            }
        }
        else {
            echo "All rma are not exists.".PHP_EOL;
            $this->_logger->info("All rma are not exists.");
        }
    }
}