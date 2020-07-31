<?php
namespace Riki\ShipLeadTime\Helper;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\File\Csv as CsvFileReader;
class Csv extends \Magento\Framework\App\Helper\AbstractHelper
{

    const CONFIG_DATA_VERSION_PATH = '/app/code/Riki/ShipLeadTime/Data/';

    CONST DATA_CSV = 'shipleadtime_data_migration.csv';
    /**
     * @var Csv
     */
    protected $csvReader;
    /**
     * @var Filesystem
     */
    protected $fileSystem;
    /**
     * @var DirectoryList
     */
    protected $directoryList;
    /**
     * @var File
     */
    protected $fileObject;

    /**
     * Csv constructor.
     * @param Context $context
     * @param \Riki\ShipLeadTime\Helper\Csv $csvReader
     * @param Filesystem $filesystem
     * @param DirectoryList $directoryList
     * @param File $file
     */
    public function __construct(
        Context $context,
        CsvFileReader $csvReader,
        Filesystem $filesystem,
        DirectoryList $directoryList,
        File $file
    ) {
        $this->csvReader = $csvReader;
        $this->fileSystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->fileObject = $file;

        parent::__construct($context);
    }

    /**
     * @return array
     */
    public function getCsvData()
    {
        $baseDir = $this->directoryList->getPath(DirectoryList::ROOT);
        $fileName= $baseDir.self::CONFIG_DATA_VERSION_PATH. self::DATA_CSV;
        $datas =  $this->csvReader->getData($fileName);
        $header = $datas[0];
        $newData = [];
        for($i=1; $i<count($datas); $i++){
            $row = $datas[$i];
            $tempRow = [];
            for($j=0; $j<count($row); $j++)
            {
                $tempRow[$header[$j]] = $row[$j];
            }
            $newData[] = $tempRow;
        }
        return $newData;
    }
}