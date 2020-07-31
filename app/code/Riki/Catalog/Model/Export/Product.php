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

use Magento\ImportExport\Model\Import;
use \Magento\Store\Model\Store;
use \Magento\CatalogImportExport\Model\Import\Product as ImportProduct;

/**
 * Product.
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
class Product extends \Magento\CatalogImportExport\Model\Export\Product
{
    /**
     * Export process
     * This function is written from core
     * set_time_limit is used for keeping the progress not timeout
     *
     * @return string
     */
    public function export()
    {
        //Execution time may be very long
        set_time_limit(0); // @codingStandardsIgnoreLine

        $writer = $this->getWriter();
        $page = 0;
        while (true) {
            ++$page;
            $entityCollection = $this->_getEntityCollection(true);
            $entityCollection->setOrder('has_options', 'asc');
            $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
            $this->_prepareEntityCollection($entityCollection);

            //custom export multi sku
            if (isset($this->_parameters['export_filter']['sku']) && is_array($this->_parameters['export_filter']['sku'])) {
                $entityCollection->addAttributeToFilter('sku', ['in' => $this->_parameters['export_filter']['sku']]);
            }

            $this->paginateCollection($page, $this->getItemsPerPage());
            if ($entityCollection->getSize() == 0) {
                break;
            }
            $exportData = $this->getExportData();
            if ($page == 1) {
                $writer->setHeaderCols($this->_getHeaderColumns());
            }
            foreach ($exportData as $dataRow) {
                $writer->writeRow($this->_customFieldsMapping($dataRow));
            }
            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }
        return $writer->getContents();
    }
}
