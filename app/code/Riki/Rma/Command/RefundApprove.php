<?php

namespace Riki\Rma\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Magento\Framework\App\State as AppState;

class RefundApprove extends Command
{
    const INPUT_KEY_FILENAME = 'file_name';

    const DEFAULT_FILENAME = 'refunds.csv';

    const ID = 'increment_id';

    /**
     * @var AppState
     */
    protected $appState;

    protected $_csvProcessor;

    protected $_directoryList;

    protected $refundManagement;

    protected $_headersIndex = [];

    protected $_fields = [
        self::ID
    ];

    /**
     * RefundApprove constructor.
     * @param AppState $appState
     * @param \Magento\Framework\File\Csv $csvProcessor
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface
     * @param \Riki\Rma\Model\RefundManagement $refundManagement
     * @param null $name
     */
    public function __construct(
        AppState $appState,
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Riki\Rma\Model\RefundManagement $refundManagement,
        $name = null
    ){
        $this->appState = $appState;
        $this->_csvProcessor = $csvProcessor;
        $this->_directoryList = $directoryList;
        $this->refundManagement = $refundManagement;

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
        $this->setName('riki:refund:approve')
            ->setDescription('Approve refund list from CSV file')
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
                foreach($migrateData as $rowNum =>  $rowData){
                    $this->processRow($rowData[$this->_headersIndex[self::ID]], $rowNum + 2, $output);
                }
            }
        }else{
            $output->writeln('<error>'. $migrateData .'</error>');
        }

        $output->writeln('<info>Process Has Finished</info>');

        return $this;
    }

    /**
     * @param $rmaId
     * @param $rowNum
     * @param $output
     * @return $this
     */
    protected function processRow($rmaId, $rowNum, $output)
    {
        $hasException = false;

        try {
            $rma = $this->refundManagement->getRmaRepository()->getByIncrementId($rmaId);
        } catch (\Exception $e) {
            $output->writeln('<error>[' . $rowNum .'][' . $rmaId . ']'. $e->getMessage() .'</error>');
            return $this;
        }

        try {
            $this->refundManagement->approve($rma->getId());
            $output->writeln('<info>[' . $rowNum .'][' . $rmaId . '] Process row has completed</info>');
        } catch (\Exception $e) {
            $hasException = true;
            $output->writeln('<error>[' . $rowNum .'][' . $rmaId . ']'. $e->getMessage() .'</error>');
        }

        if (
            $hasException &&
            $rma->getRefundMethod() == \Bluecom\Paygent\Model\Paygent::CODE
        ) {
            try {
                $this->refundManagement->processByCheck($rma->getId());
            } catch (\Exception $e) {
                $output->writeln('<error>[' . $rowNum .'][' . $rmaId . ']'. $e->getMessage() .'</error>');
            }
        }

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
            $result = $this->_csvProcessor->getData($this->_directoryList->getPath('var') . '/rim4003/' . $fileName);
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
                $this->_headersIndex[$field] = $index;
            }
        }

        return $errors;
    }
}