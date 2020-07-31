<?php

namespace Riki\Rma\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class ReturnComplete extends Command
{
    const RETURN_COMPLETE_COMMAND_MODE = 'is_return_complete_command';

    const INPUT_KEY_FILENAME = 'file_name';

    const DEFAULT_FILENAME = 'rma.csv';

    const ID = 'RmaNumber';

    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $csvProcessor;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    /**
     * @var \Riki\Rma\Model\RmaManagement
     */
    protected $rmaManagement;

    /**
     * @var \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory
     */
    protected $rmaCollectionFactory;

    /**
     * @var \Riki\Rma\Model\AmountCalculator
     */
    protected $amountCalculator;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $headersIndex = [];

    /**
     * @var array
     */
    protected $_fields = [
        self::ID
    ];

    /**
     * ReturnComplete constructor.
     * @param \Magento\Framework\File\Csv $csvProcessor
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface
     * @param \Riki\Rma\Model\RmaManagement $rmaManagement
     * @param \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory $rmaCollectionFactory
     * @param \Riki\Rma\Model\AmountCalculator $amountCalculator
     * @param \Magento\Framework\Registry $registry
     * @param null $name
     */
    public function __construct(
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Riki\Rma\Model\RmaManagement $rmaManagement,
        \Magento\Rma\Model\ResourceModel\Rma\CollectionFactory $rmaCollectionFactory,
        \Riki\Rma\Model\AmountCalculator $amountCalculator,
        \Magento\Framework\Registry $registry,
        $name = null
    ){

        $this->csvProcessor = $csvProcessor;
        $this->directoryList = $directoryList;
        $this->rmaManagement = $rmaManagement;
        $this->rmaCollectionFactory = $rmaCollectionFactory;
        $this->amountCalculator = $amountCalculator;
        $this->registry = $registry;

        parent::__construct($name);
    }

    /**
     * Get list of options and arguments for the command
     *
     * @return mixed
     */
    public function getInputList()
    {
        return [
            new InputArgument(
                self::INPUT_KEY_FILENAME,
                InputArgument::OPTIONAL,
                'Default file name: ' . self::DEFAULT_FILENAME . ' be used if file name argument is not requested.'
            ),
        ];
    }

    /**
     *
     */
    protected function configure()
    {
        $this->setName('riki:return:complete')
            ->setDescription('Complete approve return list from CSV file')
            ->setDefinition($this->getInputList());
        parent::configure();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this
     */
    protected function execute(InputInterface $input, OutputInterface $output){

        $output->writeln('<info>Starting To Approve</info>');

        $migrateData = $this->loadFileData($input);

        if(is_array($migrateData)){
            $headers = array_shift($migrateData);

            $headerErrors = $this->validateHeaders($headers);

            if(count($headerErrors)){
                foreach($headerErrors as $error){
                    $output->writeln('<error>'. $error .'</error>');
                }
            }else{
                $this->registry->unregister(self::RETURN_COMPLETE_COMMAND_MODE);
                $this->registry->register(self::RETURN_COMPLETE_COMMAND_MODE, true);

                foreach($migrateData as $rowNum =>  $rowData){

                    $rmaNumber = $rowData[$this->headersIndex[self::ID]];

                    $rma = $this->initRma($rmaNumber);

                    if ($rma) {
                        try {
                            $this->approve($rma);
                            $output->writeln('<info>[' . ($rowNum + 2) .'][' . $rmaNumber . '] Approve successfully</info>');
                        } catch (\Exception $e) {
                            $this->addError($output, $rowNum + 2, $rmaNumber, $e->getMessage());
                        }

                    } else {
                        $this->addError($output, $rowNum + 2, $rmaNumber, 'Return does not exist');
                    }
                }

                $this->registry->unregister(self::RETURN_COMPLETE_COMMAND_MODE);
            }
        }else{
            $output->writeln('<error>'. $migrateData .'</error>');
        }

        $output->writeln('<info>Process Has Finished</info>');

        return $this;
    }

    /**
     * @param $rmaNumber
     * @return bool|\Magento\Framework\DataObject
     */
    protected function initRma($rmaNumber)
    {
        /** @var \Magento\Rma\Model\ResourceModel\Rma\Collection $rmaCollection */
        $rmaCollection = $this->rmaCollectionFactory->create();

        $rma = $rmaCollection
            ->addFieldToFilter('increment_id', $rmaNumber)
            ->setPageSize(1)
            ->setCurPage(1)
            ->getFirstItem();

        if ($rma && $rma->getId()) {
            return $rma;
        }

        return false;
    }

    /**
     * @param \Magento\Rma\Model\Rma $rma
     * @return $this
     */
    protected function approve(\Magento\Rma\Model\Rma $rma)
    {
        $amountData = $this->amountCalculator->calculateReturnAmount($rma);

        foreach ($amountData as $field  =>  $value) {
            $rma->setData($field, $value);
        }

        $rma->removeExtensionData('need_save_again')->save();

        $this->rmaManagement->approve($rma->getId());

        return $this;
    }

    /**
     * @param InputInterface $input
     * @return array|string
     */
    protected function loadFileData(InputInterface $input){
        $fileName = $input->getArgument(self::INPUT_KEY_FILENAME);

        if(empty($fileName))
            $fileName = self::DEFAULT_FILENAME;

        try{
            $result = $this->csvProcessor->getData($this->directoryList->getPath('var') . '/rmm382/' . $fileName);
        }catch (\Exception $e){
            $result = $e->getMessage();
        }

        return $result;
    }

    /**
     * @param $headers
     * @return array
     */
    protected function validateHeaders($headers){

        $errors = [];

        if(is_null($headers) || !is_array($headers)){
            return ['File format is invalid'];
        }

        foreach($this->_fields as $field){

            $index = array_search($field, $headers);

            if($index === false){
                $errors[] = 'The ' . $field . ' column is missing';
            }else{
                $this->headersIndex[$field] = $index;
            }
        }

        return $errors;
    }

    /**
     * @param OutputInterface $output
     * @param $rowNum
     * @param $rmaNumber
     * @param $message
     * @return $this
     */
    protected function addError(OutputInterface $output, $rowNum, $rmaNumber, $message)
    {
        $output->writeln('<error>[' . $rowNum .'][' . $rmaNumber . '] '. $message .'</error>');

        return $this;
    }
}