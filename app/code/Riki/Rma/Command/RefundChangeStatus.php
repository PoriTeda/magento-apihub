<?php

namespace Riki\Rma\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Framework\App\Filesystem\DirectoryList;


class RefundChangeStatus extends Command
{
    const FILE_NAME = 'file_name';

    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $_readerCSV;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_varDirectory;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resourceConnection;

    /**
     * @var \Riki\Rma\Model\RefundManagement
     */
    protected $_refundManagement;

    protected $_output;

    protected $arrRmaId;

    /**
     * RefundChangeStatus constructor.
     * @param \Magento\Framework\File\Csv $reader
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Riki\Rma\Model\RefundManagement $refundManagement
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Rma\Model\Rma $rma
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        \Magento\Framework\File\Csv $reader,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Filesystem $filesystem,
        \Riki\Rma\Model\RefundManagement $refundManagement,
        \Magento\Framework\App\ResourceConnection $resourceConnection
    )
    {
        parent::__construct();
        $this->_readerCSV = $reader;
        $this->_coreRegistry = $registry;
        $this->_varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->_refundManagement = $refundManagement;
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

        $this->setName('riki:change-refund-status')
            ->setDescription('A cli change refund status change to check to check issue')
            ->setDefinition($options);
        parent::configure();
    }


    /**
     * convert data import
     *
     * @param $data
     * @return array
     */
    public function convertDataImport($data)
    {
        $dataImport = array();
        $dataImport['rma_increment_id'] = isset($data['rma_increment_id']) ? $data['rma_increment_id'] : '';
        return $dataImport;
    }

    /**
     * @param $dataImport
     * @param $row
     * @return array
     */
    public function validateData($dataImport, $row)
    {
        $data = array(
            'error' => null,
            'dataImport' => $dataImport
        );

        if ($dataImport['rma_increment_id'] == null) {
            $data['error'][] = "\t Rma increment id is not empty";
        } else if (!isset($this->arrRmaId[trim($dataImport['rma_increment_id'])])) {
            $data['error'][] = "\t Rma increment id is invalid ";
        } else {
            $dataImport['entity_id'] = $this->arrRmaId[trim($dataImport['rma_increment_id'])];
        }

        $data['dataImport'] = $dataImport;
        return $data;
    }


    /**
     * Remove BOM from a file
     *
     * @param $sourceFile
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
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
     * Get all id of rma id
     *
     * @param $arrRmaIds
     * @return mixed
     */
    public function getAllRmaId($arrRmaIds)
    {
        $connection = $this->_resourceConnection->getConnection();
        $table = $connection->getTableName('magento_rma');
        $arrRmaIds = implode(',', $arrRmaIds);
        $sql = "SELECT entity_id,increment_id FROM $table WHERE increment_id IN ($arrRmaIds)";
        $data = $connection->fetchAll($sql);
        if ($data) {
            foreach ($data as $item) {
                $this->arrRmaId[$item['increment_id']] = $item['entity_id'];
            }
        }
        return $this->arrRmaId;
    }


    /**     * @param $fileName
     * @return array
     * @throws \Exception
     */
    public function prepareData($fileName)
    {
        $dataResult = array();
        $dataRow = array();
        $this->removeBom($fileName);
        $dataCsv = $this->_readerCSV->getData($fileName);
        $rmaIds = [];

        foreach ($dataCsv as $key => $value) {
            if ($key == 0) continue;
            foreach ($value as $k => $v) {
                if (isset($dataCsv[0][$k])) {
                    $keyColum = str_replace('"', '', $dataCsv[0][$k]);
                    $dataRow[trim($keyColum)] = $v;
                }
            }

            if ($dataRow['rma_increment_id']) {
                $rmaIds[] = trim($dataRow['rma_increment_id']);
            }

            $dataResult[] = $dataRow;
        }

        //load all rma id
        $this->getAllRmaId($rmaIds);

        return $dataResult;
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $this->_output = $output;

        $fileName = $input->getArgument(self::FILE_NAME);
        if ($fileName != "") {

            $dataResult = $this->prepareData($fileName);

            $row = 2;
            foreach ($dataResult as $data) {

                // convert Data
                $dataConvert = $this->convertDataImport($data);

                $result     = $this->validateData($dataConvert, $row);
                $dataImport = $result['dataImport'];
                $errors     = $result['error'];

                $rmaIncrementId = $dataImport['rma_increment_id'];

                if (count($errors) > 0) {
                    $output->writeln("\n------------------------------------------------------------------------------------");
                    $output->writeln("[Row $row][ID: $rmaIncrementId ]  Validate error!\n");
                    $output->writeln($errors);
                } else {
                    try {
                        $entityId = $dataImport['entity_id'];
                        $this->_refundManagement->completeByCheck($entityId);
                        $output->writeln("\n------------------------------------------------------------------------------------");
                        $output->writeln("[Row $row][ID: $rmaIncrementId ]  You completed the refund by Check successfully.\n");
                        $output->writeln($errors);
                    } catch (\Exception $e) {
                        $message = str_replace('No such entity Riki\Rma\Model\Rma\Interceptor with id ', 'Rma increment id is not valid', $e->getMessage());
                        $output->writeln("\n------------------------------------------------------------------------------------");
                        $output->writeln("[Row $row][ID: $rmaIncrementId ] " . $message . "\n");
                        $output->writeln($errors);
                    }
                }
                $row++;
            }
        }
    }


}
