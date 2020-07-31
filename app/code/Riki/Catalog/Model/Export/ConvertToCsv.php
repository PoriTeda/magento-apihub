<?php
/**
 * Catalog.
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Catalog
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Catalog\Model\Export;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Ui\Component\MassAction\Filter;

/**
 * ConvertToCsv.
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Catalog
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class ConvertToCsv extends \Magento\Ui\Model\Export\ConvertToCsv
{

    /**
     * ConvertToCsv constructor.
     *
     * @param Filesystem                                       $filesystem            Filesystem
     * @param Filter                                           $filter                Filter
     * @param \Magento\Ui\Model\Export\MetadataProvider        $metadataProvider      MetadataProvider
     * @param \Magento\ImportExport\Model\Export               $modelExportProduct    Export
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory           FileFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilder     $searchCriteriaBuilder SearchCriteriaBuilder
     * @param \Magento\Catalog\Api\ProductRepositoryInterface  $productRepository     ProductRepositoryInterface
     * @param DirectoryList                                    $directoryList         DirectoryList
     * @param \Magento\Framework\File\Csv                      $csvFile               Csv
     * @param Filesystem\Driver\File                           $fileDirectory         File
     * @param \Magento\Framework\Stdlib\DateTime\DateTime      $datetime              DateTime
     */
    public function __construct(
        Filesystem $filesystem,
        Filter $filter,
        \Magento\Ui\Model\Export\MetadataProvider $metadataProvider,
        \Magento\ImportExport\Model\Export $modelExportProduct,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\File\Csv $csvFile,
        \Magento\Framework\Filesystem\Driver\File $fileDirectory,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime
    ) {
        $this->filter = $filter;
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->metadataProvider = $metadataProvider;
        $this->_modelExportProduct = $modelExportProduct;
        $this->_fileFactory = $fileFactory;

        $this->_directoryList = $directoryList;
        $this->_csvFile = $csvFile;
        $this->_fileDirectory = $fileDirectory;
        $this->_datetime = $datetime;

        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_productRepository = $productRepository;
    }

    /**
     * GetProductCsvFile
     *
     * @return \Magento\Framework\App\ResponseInterface
     *
     * @throws LocalizedException
     */
    public function getProductCsvFile()
    {
        $component = $this->filter->getComponent();

        $this->filter->prepareComponent($component);
        $this->filter->applySelectionOnTargetProvider();

        $searchResult = $component->getContext()->getDataProvider()->getSearchResult();

        $documentEntityIds = array();
        foreach ($searchResult->getItems() as $document) {
            $documentEntityIds[] = $document->getData('entity_id');
        }

        $searchCriteria = $this->_searchCriteriaBuilder->addFilter('entity_id', $documentEntityIds, 'in')->create();
        $products = $this->_productRepository->getList($searchCriteria)->getItems();

        $productSkus = array();
        foreach ($products as $product) {
            $productSkus[] = $product->getData('sku');
        }

        if (count($productSkus)) {

            $params = array(
                "entity" => "catalog_product",
                "file_format" => "csv",
                "frontend_label" => "",
                "attribute_code" => "",
                "export_filter" => array(
                    "sku" => $productSkus
                ),
            );
            $this->_modelExportProduct->setData($params);

            $dataProductExport = $this->_modelExportProduct->export();

            $dataProductExportSeparateColumn = $this->handleExportDataProduct($dataProductExport);

            return $this->_fileFactory->create(
                $this->_modelExportProduct->getFileName(),
                $dataProductExportSeparateColumn,
                DirectoryList::VAR_DIR,
                $this->_modelExportProduct->getContentType()
            );
        }

    }

    /**
     * HandleExportDataProduct
     *
     * @param array $productsCSV Array
     *
     * @return array
     */
    public function handleExportDataProduct($productsCSV)
    {
        //read file csv
        $productsCSVArray = $this->readProductArrayCSV($productsCSV);

        //find consistency columns
        $aColumnsAddition = array();
        $aColumnsProductMapping = array();
        foreach ($productsCSVArray as $keyProduct => $productCSVArray) {
            if (isset($productCSVArray['additional_attributes'])) {
                $aColumnsProductMapping[$keyProduct] = array();
                $sProductAttrs = $productCSVArray['additional_attributes'];
                $aProductAttrItems = $this->getValueWithCommas($sProductAttrs);
                foreach ($aProductAttrItems as $aProductAttrItemKey => $aProductAttrItemValue) {
                    if (!in_array($aProductAttrItemKey, $aColumnsAddition)) {
                        $aColumnsAddition[] = $aProductAttrItemKey;
                    }
                    $aColumnsProductMapping[$keyProduct][$aProductAttrItemKey] = $aProductAttrItemValue;
                }
            }
        }

        //match value addition attributes
        foreach ($productsCSVArray as $keyProduct => &$productCSVArray) {
            if (isset($productCSVArray['additional_attributes'])) {
                if (count($aColumnsAddition)) {
                    foreach ($aColumnsAddition as $aColumnAddition) {
                        if (isset($aColumnsProductMapping[$keyProduct][$aColumnAddition])) {
                            $productCSVArray[$aColumnAddition] = $aColumnsProductMapping[$keyProduct][$aColumnAddition];
                        } else {
                            $productCSVArray[$aColumnAddition] = '';
                        }
                    }
                }
                unset($productCSVArray['additional_attributes']);
            }
        }

        $productsCSVString = $this->writeProductArrayCSV($productsCSVArray);

        return $productsCSVString;
    }

    /**
     * ReadProductArrayCSV
     *
     * @param string $productsCSV String
     *
     * @return array
     */
    public function readProductArrayCSV($productsCSV)
    {

        $tmpFile = $this->_directoryList->getPath('tmp') . DIRECTORY_SEPARATOR . 'export-tmp-products-' . $this->_datetime->date('Ymd-Hmi') . '.csv';
        $this->_fileDirectory->filePutContents($tmpFile, $productsCSV);
        $productFormat = $this->_csvFile->getData($tmpFile);

        $productFormatFinal = array();
        if (count($productFormat)) {
            foreach ($productFormat as $key => $csvData) {
                if ($key == 0) {
                    $headers = $csvData;
                } else {
                    $item = array();
                    $i = 0;
                    foreach ($headers as $header) {
                        if (isset($csvData[$i])) {
                            $item[$header] = $csvData[$i];
                        }
                        $i++;
                    }
                    if (count($item) == count($headers)) {
                        $productFormatFinal[] = $item;
                    }
                }
            }
        }

        return $productFormatFinal;
    }

    /**
     * GetValueWithCommas
     *
     * @param string $sAttribute String
     *
     * @return array
     */
    public function getValueWithCommas($sAttribute)
    {
        $aAttributeReturns = array();

        $aAttributeEqualKeys = array();
        $aAttributeEqualValues = array();

        $aAttributes = explode("=", $sAttribute);
        foreach ($aAttributes as $key => $aAttribute) {
            if ($key == 0) {
                $aAttributeEqualKeys[] = $aAttribute;
            } else {
                if ($key == count($aAttributes) - 1) {
                    $aAttributeEqualValues[] = $aAttribute;
                } else {
                    $aAttributeFields = explode(",", $aAttribute);
                    $sFinalKey = array_pop($aAttributeFields);
                    $sFirstValue = implode(",", $aAttributeFields);
                    $aAttributeEqualKeys[] = $sFinalKey;
                    $aAttributeEqualValues[] = $sFirstValue;
                }
            }
        }

        if (count($aAttributeEqualKeys) == count($aAttributeEqualValues)) {
            foreach ($aAttributeEqualKeys as $key => $aAttributeEqualKey) {
                $aAttributeReturns[$aAttributeEqualKey] = $aAttributeEqualValues[$key];
            }
        }

        return $aAttributeReturns;
    }

    /**
     * WriteProductArrayCSV
     *
     * @param array $productsCSV Array
     *
     * @return array
     */
    public function writeProductArrayCSV($productsCSV)
    {

        if (count($productsCSV)) {
            array_unshift($productsCSV, array_keys($productsCSV[0]));
        }

        $tmpFile = $this->_directoryList->getPath('tmp') . DIRECTORY_SEPARATOR . 'export-tmp-products-' . $this->_datetime->date('Ymd-Hmi') . '.csv';
        $this->_csvFile->saveData($tmpFile, $productsCSV);
        $productsStringCSV = $this->_fileDirectory->fileGetContents($tmpFile);

        return $productsStringCSV;
    }
}
