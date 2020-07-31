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

use Magento\CatalogImportExport\Model\Import\Product as ImportProductModel;
use Magento\ImportExport\Model\Import as ImportModel;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

/**
 * RowCustomizer.
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
class RowCustomizer extends \Magento\BundleImportExport\Model\Export\RowCustomizer
{
    /**
     * CleanNotBundleAdditionalAttributes
     *
     * @param array $dataRow Array
     *
     * @return array
     */
    protected function cleanNotBundleAdditionalAttributes($dataRow)
    {
        if (!empty($dataRow['additional_attributes'])) {

            //fix bug if attribute_value has Commas
            $additionalAttributes = $this->getValueWithCommas($dataRow['additional_attributes']);

            $dataRow['additional_attributes'] = $this->getNotBundleAttributes($additionalAttributes);
        }

        return $dataRow;
    }

    /**
     * Get Value With Commas
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

        $aAttributes = explode(ImportProductModel::PAIR_NAME_VALUE_SEPARATOR, $sAttribute);
        foreach ($aAttributes as $key => $aAttribute) {
            if ($key == 0) {
                $aAttributeEqualKeys[] = $aAttribute;
            } else {
                if ($key == count($aAttributes) - 1) {
                    $aAttributeEqualValues[] = $aAttribute;
                } else {
                    $aAttributeFields = explode(ImportModel::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $aAttribute);
                    $sFinalKey = array_pop($aAttributeFields);
                    $sFirstValue = implode(ImportModel::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $aAttributeFields);
                    $aAttributeEqualKeys[] = $sFinalKey;
                    $aAttributeEqualValues[] = $sFirstValue;
                }
            }
        }

        if (count($aAttributeEqualKeys) == count($aAttributeEqualValues)) {
            foreach ($aAttributeEqualKeys as $key => $aAttributeEqualKey) {
                $aAttributeReturns[] = $aAttributeEqualKey . ImportProductModel::PAIR_NAME_VALUE_SEPARATOR . $aAttributeEqualValues[$key];
            }
        }

        return $aAttributeReturns;
    }

    /**
     * Retrieve formatted bundle options
     *
     * @param \Magento\Catalog\Model\Product $product Product
     *
     * @return string
     */
    public function getFormattedBundleOptionValues($product):string
    {
        /**
         * Collection
         *
         * @var \Magento\Bundle\Model\ResourceModel\Option\Collection $optionsCollection Collection
         */
        $optionsCollection = $product->getTypeInstance()
            ->getOptionsCollection($product)
            ->setOrder('position', Collection::SORT_ORDER_ASC);

        $bundleData = '';
        foreach ($optionsCollection as $option) {
            $bundleData .= $this->getFormattedBundleSelections(
                $this->getFormattedOptionValues($option),
                $product->getTypeInstance()
                    ->getSelectionsCollection([$option->getId()], $product)
                    ->setOrder('position', Collection::SORT_ORDER_ASC)
            );
        }

        return rtrim($bundleData, ImportProductModel::PSEUDO_MULTI_LINE_SEPARATOR);
    }

}
