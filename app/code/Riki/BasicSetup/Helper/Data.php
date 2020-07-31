<?php
/**
 * Basic Setup Module
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\BasicSetup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\BasicSetup\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\File\Csv;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
/**
 * Class Data
 *
 * @category  RIKI
 * @package   Riki\BasicSetup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Data extends AbstractHelper
{

    const CONFIG_DATA_VERSION_PATH = '/app/code/Riki/BasicSetup/Data/Version/';

    const FILE_ADMIN_ROLE = 'admin_role.csv';

    const FILE_ADMIN_USER = 'user_account.csv';

    const FILE_ADMIN_PASS = 'user_password_history.csv';

    const FILE_ADMIN_ROLE_RULES = 'admin_role_rules.csv';

    const FILE_ADMIN_CATEGORIES = 'categories.csv';

    const FILE_ADMIN_CATEGORIES_REMOVE = 'categories_remove.csv';
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
     * Data constructor.
     * @param Context $context
     * @param Csv $csvReader
     * @param Filesystem $filesystem
     */
    public function __construct(
        Context $context,
        Csv $csvReader,
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
     * Read content from csv file
     *
     * @param $file
     * @param bool $command
     * @param bool $combine
     * @param int $keyColumn
     * @param int $combineColumn
     * @return array
     */
    public function getCsvContent($file, $command = false, $combine = false, $keyColumn = 0, $combineColumn = 0)
    {
        $baseDir = $this->directoryList->getPath(DirectoryList::ROOT);
        if (!$command) {
            $fileName= $baseDir.self::CONFIG_DATA_VERSION_PATH. $file;
        } else {
            $fileName = $file;
        }
        try {
            $rawData = $this->csvReader->getData($fileName);
            if ($combine) {
                return $this->combineData($rawData, $keyColumn, $combineColumn);
            }
            return $rawData;
        } catch (\Exception $e) {
            $this->_logger->info($e->getMessage());
            return [];
        }
    }

    /**
     * Combine shipment number if data has the same order number
     *
     * @param $rawData
     * @param $keyColumn
     * @param $combineColumn
     * @return array
     */
    private function combineData($rawData, $keyColumn, $combineColumn)
    {
        $currentOrderNumber = 0;
        $separateKey = ';';
        if (!empty($rawData)) {
            $newData = [];
            foreach ($rawData as $row) {
                if (isset($row[$keyColumn])) {
                    if ($currentOrderNumber == $row[$keyColumn]) {
                        if (isset($row[$combineColumn])) {
                            $newData[$row[$keyColumn]][$combineColumn] .= $separateKey.$row[$combineColumn];
                        }
                    } else {
                        $newData[$row[$keyColumn]] = $row;
                        $currentOrderNumber = $row[$keyColumn];
                    }
                }
            }
            return $newData;
        }
        return $rawData;
    }
    /**
     * @param $filename
     * @return bool
     */
    public function checkFileExist($filename)
    {
        $baseDir = $this->directoryList->getPath(DirectoryList::ROOT);
        $filePath = $baseDir.'/'.$filename;
        if ($this->fileObject->isExists($filePath)) {
            return true;
        }
        return false;
    }
}