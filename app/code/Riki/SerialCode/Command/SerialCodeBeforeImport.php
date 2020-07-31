<?php
namespace Riki\SerialCode\Command;
use Magento\Catalog\Model\Product;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
class SerialCodeBeforeImport extends Command
{
    /**
     * @var \Riki\SerialCode\Model\ResourceModel\SerialCode\CollectionFactory
     */
    protected $_serialCodeCollectionFactory;

    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $_csvReader;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;
    /**
     * @var array
     */
    protected $_header = [];
    /**
     * @var array
     */
    protected $_existed = [];
    /**
     * @var array
     */
    protected $_statistic = [];
    /**
     * @var array
     */
    protected $_status = [];
    /**
     * @var array
     */
    protected $_validCode = [];
    /**
     * @var array
     */
    protected $_dataImport = [];
    /**
     * @var \Magento\Framework\File\Csv $_readerCSV
     */
    protected $_readerCSV;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface $_time
     */
    protected $_time;
    const FILE_NAME ='file_name';

    public function __construct(
        \Magento\Framework\File\Csv $reader,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Riki\SerialCode\Model\ResourceModel\SerialCode\CollectionFactory $collectionFactory,
        \Riki\SerialCode\Logger\LoggerCSV $loggerInterface
    )
    {
        $this->_readerCSV = $reader;
        $this->_time      = $timezoneInterface;
        $this->_serialCodeCollectionFactory = $collectionFactory;
        $this->_logger = $loggerInterface;
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
                InputArgument::REQUIRED,
                'Name of file to import'
            )
        ];
        $this->setName('riki:serial_code:before-import')
            ->setDescription('A cli Import Serail Code')
            ->setDefinition($options);
        parent::configure();
    }
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileName = $input->getArgument(self::FILE_NAME);
        $errorValidate  = false;
        try {
            $csvData = $this->_readerCSV->getData($fileName);;
            //skip header
            $header = array_shift($csvData);
            $headerError = $this->setHeader($header)->validateHeader();
            if (sizeof($headerError)) {
                $output->writeln("\n------------------------------------------------------------------------------------");
                $output->writeln("Header error please view log file var/log/serial_code_import.log\n");
                $this->_logger->info(implode('-', $headerError));
                exit();
            }
            $dataImport = $this->buildArray($csvData);
            if (!sizeof($dataImport)) {
                $output->writeln("\n------------------------------------------------------------------------------------");
                $output->writeln("The was no record to import\n");
                exit();
            }
            $this->prepareData($dataImport);
            $success = 0;
            foreach ($dataImport as $key => $value) {
                $value = array_map('trim', $value);
                if ($this->validateRow($key, $value) === true) {
                    $success++;
                }
            }
            if (sizeof($this->_statistic)) {
                $errorValidate  = true;
                foreach ($this->_statistic as $key => $value) {
                    $value = array_unique($value);
                    if (strpos($key, 'EMPTY_') !== false) {
                        $detect = explode('EMPTY_', $key);
                        $msg = __('%1 is empty in rows: %2', strtoupper($detect[1]), implode(', ', $value));
                        $messages['error'][] = $msg;
                        continue;
                    }
                    switch ($key) {
                        case 'dateInvalid':
                            $msg = __('Date is not valid in rows: %1', implode(', ', $value));
                            break;
                        case 'issuedPoint':
                            $msg = __('Issued point is not valid in rows: %1', implode(', ', $value));
                            break;
                        case 'limitPer':
                            $msg = __('Limit Per Customer is not valid in rows: %1', implode(', ', $value));
                            break;
                        case 'codeExisted':
                            $msg = __('Serial code is already existed in rows: %1', implode(', ', $value));
                            break;
                        case 'wbsInvalid':
                            $msg = __('WBS format is not valid in rows: %1', implode(', ', $value));
                            break;
                        case 'codeDuplicate':
                            $msg = __('Serial code is duplicated in rows: %1', implode(', ', $value));
                            break;
                        default:
                            $msg = __('Data error in rows: %1', implode(', ', $value));
                            break;
                    }
                    $this->_logger->info($msg);
                }
            }
            if ($success) {
                $output->writeln("\n------------------------------------------------------------------------------------");
                $output->writeln("Validated ".$success." row\n");
            }
            if ($errorValidate) {
                $output->writeln("\n------------------------------------------------------------------------------------");
                $output->writeln("Please check file var/log/serial_code_import.log\n");
            }
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            exit();
        }
    }
    /**
     * Set header for import data
     *
     * @param array $header
     * @return $this
     */
    private function setHeader($header)
    {
        $this->_header = array_map('strtolower', $header);
        return $this;
    }
    /**
     * Validate header index
     *
     * @return array
     */
    private function validateHeader()
    {
        $errors = [];
        $columnRequired = [
            'serial_code', 'issued_point', 'serial_code_expiration_date',
            'serial_code_shipping_date', 'serial_code_user_customer_code',
            'campaign_id', 'limit_per_customer', 'wbs', 'account_code'
        ];
        foreach ($columnRequired as $column) {
            if (array_search($column, $this->_header) === false) {
                $errors[] = __('Missing column %1', strtoupper($column));
            }
        }
        return $errors;
    }
    /**
     * Combine csv data into key-value
     *
     * @param array $csvData
     * @return array
     */
    private function buildArray($csvData)
    {
        $result = [];
        foreach ($csvData as $key => $value) {
            $result[] = array_combine($this->_header, $value);
        }
        return $result;
    }
    /**
     * Prepare existing serial code to validate
     *
     * @param array $dataImport
     * @return $this
     */
    private function prepareData($dataImport)
    {
        $allSerialCodes = array_map(function($value) {
            return $value['serial_code'];
        }, $dataImport);
        $allSerialCodes = array_filter($allSerialCodes);
        if (!sizeof($allSerialCodes)) {
            return $this;
        }
        /** @var \Riki\SerialCode\Model\ResourceModel\SerialCode\Collection $collection */
        $collection = $this->_serialCodeCollectionFactory->create();
        $collection->addFieldToFilter('serial_code', ['in' => $allSerialCodes]);
        if (!$collection->getSize()) {
            return $this;
        }
        $this->_existed = array_map(function($value) {
            return $value['serial_code'];
        }, $collection->getData());
        return $this;
    }
    /**
     * Validate row import
     *
     * @param int $key
     * @param array $value
     * @return bool
     */
    private function validateRow($key, $value)
    {
        $key++;
        $isError = false;;
        $requiredField = ['serial_code', 'serial_code_shipping_date', 'wbs', 'account_code', 'issued_point'];
        foreach ($requiredField as $field) {
            if (!\Zend_Validate::is($value[$field], 'NotEmpty')) {
                $isError = true;
                $this->_statistic['EMPTY_'.$field][] = $key;
            }
        }
        if (\Zend_Validate::is($value['serial_code_shipping_date'], 'notEmpty') && !\DateTime::createFromFormat('Y/m/d H:i:s', $value['serial_code_shipping_date'])) {
            $this->_statistic['dateInvalid'][] = $key;
            $isError = true;
        }
        if (\Zend_Validate::is($value['serial_code_regitration_date'], 'notEmpty') && !\DateTime::createFromFormat('Y/m/d H:i:s', $value['serial_code_regitration_date'])) {
            $this->_statistic['dateInvalid'][] = $key;
            $isError = true;
        }
        if (\Zend_Validate::is($value['serial_code_expiration_date'], 'notEmpty') && !\DateTime::createFromFormat('Y/m/d H:i:s', $value['serial_code_expiration_date'])) {
            $this->_statistic['dateInvalid'][] = $key;
            $isError = true;
        }
        if (\Zend_Validate::is($value['issued_point'], 'notEmpty') && !\Zend_Validate::is($value['issued_point'], 'Int')) {
            $this->_statistic['issuedPoint'][] = $key;
        } elseif (\Zend_Validate::is($value['issued_point'], 'notEmpty') &&
            \Zend_Validate::is($value['issued_point'], 'Int') &&
            $value['issued_point'] <= 0
        ) {
            $this->_statistic['issuedPoint'][] = $key;
            $isError = true;
        }
        if (\Zend_Validate::is($value['limit_per_customer'], 'notEmpty') && !\Zend_Validate::is($value['limit_per_customer'], 'Int')) {
            $this->_statistic['limitPer'][] = $key;
        } elseif (\Zend_Validate::is($value['limit_per_customer'], 'notEmpty') &&
            \Zend_Validate::is($value['limit_per_customer'], 'Int') &&
            $value['limit_per_customer'] <= 0
        ) {
            $this->_statistic['limitPer'][] = $key;
            $isError = true;
        }
        if (\Zend_Validate::is($value['serial_code'], 'notEmpty') && in_array($value['serial_code'], $this->_existed)) {
            $this->_statistic['codeExisted'][] = $key;
            $isError = true;
        }
        $wbsValidate = new \Zend_Validate_Regex('/^AC-\d{8}$/');
        if (\Zend_Validate::is($value['wbs'], 'notEmpty') && !$wbsValidate->isValid($value['wbs'])) {
            $this->_statistic['wbsInvalid'][] = $key;
            $isError = true;
        }
        if (\Zend_Validate::is($value['serial_code'], 'notEmpty') && in_array($value['serial_code'], $this->_validCode)) {
            $this->_statistic['codeDuplicate'][] = $key;
            $isError = true;
        }
        if (!$isError) {
            $this->_validCode[] = $value['serial_code'];
        }
        return !$isError;
    }

}