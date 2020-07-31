<?php
namespace Riki\CreateProductAttributes\Model;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class InitialData
 * @package Riki\ProductGroup\Model
 */
class InitialData
{
    const CONFIG_DATA_FILE_NAME = '/app/code/Riki/CreateProductAttributes/Data/product_group.csv';

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
     * @var \Riki\ProductGroup\Model\GroupsFactory
     */
    protected $groupsFactory;

    /**
     * InitialData constructor.
     * @param Csv $csvReader
     * @param Filesystem $filesystem
     * @param DirectoryList $directoryList
     */
    public function __construct(
        Csv $csvReader,
        Filesystem $filesystem,
        DirectoryList $directoryList
    )
    {
        $this->csvReader = $csvReader;
        $this->fileSystem = $filesystem;
        $this->directoryList = $directoryList;
    }

    /**
     *
     */
    public function importData(){
        $data = $this->getCsvContent();

    }

    /**
     * @return array
     */
    public function getCsvContent()
    {
        $baseDir = $this->directoryList->getPath(DirectoryList::ROOT);
        $fileName= $baseDir.self::CONFIG_DATA_FILE_NAME;
        return $this->csvReader->getData($fileName);
    }
    public function getProductGroups($indexNumber, $oldData)
    {
        $datas = $this->getCsvContent();
        $newData = [];
        foreach($datas as $data){
            if($data && !in_array($data[$indexNumber],$newData) && !in_array($data[$indexNumber],$oldData)){
                $newData[] = $data[$indexNumber];
            }
        }
        return $newData;
    }
}