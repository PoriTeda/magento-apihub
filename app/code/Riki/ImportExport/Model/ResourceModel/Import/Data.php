<?php
/**
 * Import Data
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ImportExport\Model\ResourceModel\Import
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ImportExport\Model\ResourceModel\Import;

/**
 * Class Data
 *
 * @category  RIKI
 * @package   Riki\ImportExport\Model\ResourceModel\Import
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Data extends \Magento\ImportExport\Model\ResourceModel\Import\Data
{
    /**
     * Format post_code.
     *
     * @return mixed|null
     */
    public function getNextBunch()
    {
        if (null === $this->_iterator) {
            $this->_iterator = $this->getIterator();
            $this->_iterator->rewind();
        }
        $dataRow = null;
        if ($this->_iterator->valid()) {
            $encodedData = $this->_iterator->current();
            if (array_key_exists(0, $encodedData) && $encodedData[0]) {
                $dataRow = $this->jsonHelper->jsonDecode($encodedData[0]);
                $this->_iterator->next();
            }
        }
        if (!$dataRow) {
            $this->_iterator = null;
        }

        $returnData = [];
        if (is_array($dataRow) && count($dataRow)) {
            foreach ($dataRow as $key => $row) {
                if (isset($row['postcode']) && trim($row['postcode'])) {
                    $row['postcode'] = rtrim(substr_replace(str_replace('-', '', trim($row['postcode'], "\t\n ")), '-', 3, 0));
                }

                $returnData[$key] = $row;
            }
        }

        return $returnData;
    }
}
