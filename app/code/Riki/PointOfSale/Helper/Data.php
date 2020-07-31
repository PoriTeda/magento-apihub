<?php
namespace Riki\PointOfSale\Helper;

use Riki\PointOfSale\Api\PointOfSaleRepositoryInterface;
use Magento\Framework\File\Csv;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    
    protected $backendUrl;

    /**
     * @var \Riki\DeliveryType\Model\ResourceModel\Delitype\CollectionFactory
     */
    protected $deliCollectionFactory;

    /** @var PointOfSaleRepositoryInterface  */
    protected $pointOfSaleRepository;

    /**
     * @var \Wyomind\PointOfSale\Model\PointOfSale
     */
    protected $pointOfSale;

    /**
     * @var
     */
    protected $places;

    /**
     * list of place id
     *
     * @var array
     */
    protected $placeIds = [];

    /**
     * @var array
     */
    protected $placesByStore = [];

    const CONFIG_DATA_VERSION_PATH = '/app/code/Riki/PointOfSale/Data/';

    CONST DATA_CSV = 'warehouse.csv';
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
     * @param \Magento\Framework\App\Helper\Context $context
     * @param PointOfSaleRepositoryInterface $pointOfSaleRepository
     * @param \Magento\Framework\Api\FilterBuilder $filterBuilder
     * @param \Riki\DeliveryType\Model\ResourceModel\Delitype\CollectionFactory $deliCollectionFactory
     * @param \Wyomind\PointOfSale\Model\PointOfSale $pointOfSale
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Riki\PointOfSale\Api\PointOfSaleRepositoryInterface $pointOfSaleRepository,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Riki\DeliveryType\Model\ResourceModel\Delitype\CollectionFactory $deliCollectionFactory,
        \Wyomind\PointOfSale\Model\PointOfSale $pointOfSale,
        Csv $csvReader,
        Filesystem $filesystem,
        DirectoryList $directoryList,
        File $file
    ) {
        $this->deliCollectionFactory = $deliCollectionFactory ;
        $this->pointOfSaleRepository = $pointOfSaleRepository;
        $this->pointOfSale = $pointOfSale;
        $this->csvReader = $csvReader;
        $this->fileSystem = $filesystem;
        $this->directoryList = $directoryList;
        $this->fileObject = $file;
        parent::__construct($context);
    }

    /**
     * @return PointOfSaleRepositoryInterface
     */
    public function getPointOfSaleRepository()
    {
        return $this->pointOfSaleRepository;
    }

    /**
     * get categories array.
     *
     * @return array
     */
    public function getDelitypeArray()
    {
        $deliArray = $this->deliCollectionFactory->create()->load();

        $deli = [];
        foreach ($deliArray as $deliType) {
                $deli[] = [
                    'label' => $deliType->getName(),
                    'value' => $deliType->getCode(),
                ];
        }

        return $deli;
    }

    /**
     * @param $store
     * @return \Magento\Framework\Api\ExtensibleDataInterface[]
     */
    public function getPlacesByStore($store)
    {

        if ($store instanceof \Magento\Store\Model\Store) {
            $store = $store->getId();
        }

        if (!isset($this->placesByStore[$store])) {
            $this->placesByStore[$store] = $this->pointOfSale->getPlaces()->getPlacesByStoreId($store, null)->getItems();
        }

        return $this->placesByStore[$store];
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return \Magento\Framework\Api\ExtensibleDataInterface[]
     */
    public function getPlacesByQuote(\Magento\Quote\Model\Quote $quote)
    {
        return $this->getPlacesByStore($quote->getStoreId());
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return \Magento\Framework\Api\ExtensibleDataInterface[]
     */
    public function getPlacesByOrder(\Magento\Sales\Model\Order $order)
    {
        return $this->getPlacesByStore($order->getStoreId());
    }

    /**
     * @return array
     */
    public function getPlaces()
    {
        if (!$this->places) {

            $this->places = [];

            $places = $this->pointOfSale->getPlaces();

            foreach ($places as $place) {
                $this->places[$place->getId()] = $place;
            }
        }

        return $this->places;
    }

    /**
     * Get list of place ids
     *
     * @return array
     */
    public function getPlaceIds()
    {
        if (!$this->placeIds) {
            $this->placeIds = [];
            $places = $this->pointOfSale->getPlaces();
            foreach ($places as $place) {
                array_push($this->placeIds, $place->getId());
            }
        }

        return $this->placeIds;
    }

    /**
     * get limit places
     *      is used for plugin to control a difference between FO and BO
     * @return array
     */
    public function getLimitPlaces()
    {
        return [];
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
