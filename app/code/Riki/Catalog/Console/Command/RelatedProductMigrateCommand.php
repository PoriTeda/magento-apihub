<?php

namespace Riki\Catalog\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Magento\Framework\App\State as AppState;

class RelatedProductMigrateCommand extends Command
{
    const LINK_TYPE = 'related';

    const INPUT_KEY_FILENAME = 'file_name';

    const DEFAULT_FILENAME = 'RELATED_COMMODITY_A.csv';

    const COMMODITY_CODE = 'COMMODITY_CODE';
    const LINK_COMMODITY_CODE = 'LINK_COMMODITY_CODE';
    const DISPLAY_ORDER = 'DISPLAY_ORDER';

    /**
     * @var AppState
     */
    protected $appState;

    protected $_csvProcessor;

    protected $_directoryList;

    protected $_productLinkResource;

    protected $_productRepository;

    protected $_headersIndex = [];

    protected $_fields = [
        self::COMMODITY_CODE,
        self::LINK_COMMODITY_CODE,
        self::DISPLAY_ORDER
    ];

    protected $_preparedLinks = [];

    public function __construct(
        AppState $appState,
        \Magento\Framework\File\Csv $csvProcessor,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Magento\Catalog\Model\ResourceModel\Product\Link $productLinkResource,
        $name = null
    ){
        $this->appState = $appState;
        $this->_csvProcessor = $csvProcessor;
        $this->_directoryList = $directoryList;
        $this->_productRepository = $productRepositoryInterface;
        $this->_productLinkResource = $productLinkResource;

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

    protected function configure()
    {
        $this->setName('catalog:related-product:migrate')
            ->setDescription('Migrate related products')
            ->setDefinition($this->getInputList());
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output){

        $this->appState->setAreaCode('catalog');

        $output->writeln('<info>Starting To Migrate Related Product Data</info>');

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
                    $this->prepareRowData($rowData, $rowNum + 2, $output);
                }

                $output->writeln('<info>Starting To Save Related Product Data</info>');
                $this->saveLinks($output);
            }
        }else{
            $output->writeln('<error>'. $migrateData .'</error>');
        }

        $output->writeln('<info>The Related Product Migration Process Has Finished</info>');
    }

    /**
     * @param OutputInterface $output
     * @return $this
     */
    protected function saveLinks(OutputInterface $output){
        foreach($this->_preparedLinks as $sku   =>  $linkItems){
            try{
                $product = $this->_productRepository->get($sku);

                $this->_productLinkResource->saveProductLinks($product, $linkItems, \Magento\Catalog\Model\Product\Link::LINK_TYPE_RELATED);

                $output->writeln('<info>SKU: ' . $sku . ', migrated successfully!</info>');
            }catch(\Exception $e){
                $output->writeln('<error>SKU: ' . $sku . ', message: ' . $e->getMessage() . ' </error>');
            }
        }

        return $this;
    }

    /**
     * @param $rowData
     * @param $rowNum
     * @param OutputInterface $output
     * @return $this
     */
    protected function prepareRowData($rowData, $rowNum, OutputInterface $output){
        $errors = $this->validateRowData($rowData);

        if(count($errors)){
            foreach($errors as $error){
                $output->writeln('<error>[' . $rowNum . '] ' .$error .'</error>');
            }
        }else{

            $sku = $rowData[$this->_headersIndex[self::COMMODITY_CODE]];
            if(!isset($this->_preparedLinks[$sku])){
                $this->_preparedLinks[$sku] = [];
            }

            $linkedId = $this->_productRepository->get($rowData[$this->_headersIndex[self::LINK_COMMODITY_CODE]])->getId();

            $this->_preparedLinks[$sku][$linkedId] = ['position' =>  (int)$rowData[$this->_headersIndex[self::DISPLAY_ORDER]]];
        }

        return $this;
    }

    /**
     * @param $rowData
     * @return array
     */
    protected function validateRowData($rowData){

        $errors = [];

        $skuIndex = $this->_headersIndex[self::COMMODITY_CODE];
        $skuLinkIndex = $this->_headersIndex[self::LINK_COMMODITY_CODE];
        $orderIndex = $this->_headersIndex[self::DISPLAY_ORDER];

        if(
            !isset($rowData[$skuIndex])
            || !isset($rowData[$skuLinkIndex])
            || !isset($orderIndex)
        ){
            return ['Data is invalid'];
        }

        if($rowData[$skuIndex] == ''){
            $errors[] = 'Column: ' . self::COMMODITY_CODE . ', is required field';
        }else{
            if(!$this->isExitedSku($rowData[$skuIndex])){
                $errors[] = 'Column: ' . self::COMMODITY_CODE . ', sku "' . $rowData[$skuIndex] . '" does not exist"';
            }
        }

        if($rowData[$skuLinkIndex] == ''){
            $errors[] = 'Column: ' . self::LINK_COMMODITY_CODE . ', is required field';
        }else{

            if($rowData[$skuLinkIndex] == $rowData[$skuIndex]){
                $errors[] = 'Column: ' . self::LINK_COMMODITY_CODE . ', sku "' . $rowData[$skuLinkIndex] . '" is invalid"';
            }
            if(!$this->isExitedSku($rowData[$skuLinkIndex])){
                $errors[] = 'Column: ' . self::LINK_COMMODITY_CODE . ', sku "' . $rowData[$skuLinkIndex] . '" does not exist"';
            }
        }

        if($rowData[$orderIndex] != ''){
            if($rowData[$orderIndex] != (string)(intval($rowData[$orderIndex]))){
                $errors[] = 'Column: ' . self::DISPLAY_ORDER . ', value "' . $rowData[$orderIndex] . '" is invalid';
            }
        }

        return $errors;
    }

    /**
     * @param $sku
     * @return bool|int|null
     */
    protected function isExitedSku($sku){
        try{
            $product = $this->_productRepository->get($sku);
        }catch (\Magento\Framework\Exception\NoSuchEntityException $e){
            return false;
        }

        return $product->getId();
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
            $result = $this->_csvProcessor->getData($this->_directoryList->getPath('var') . '/import/' . $fileName);
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