<?php
/**
 * Import product
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ImportExport\Model\Import
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ImportExport\Model\Import;

/**
 * Class Product
 *
 * @category  RIKI
 * @package   Riki\ImportExport\Model\Import
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Product extends \Magento\CatalogImportExport\Model\Import\Product\Validator
{

    protected $attributeIgnoreOnImports= array(
        'created_at',
        'giftcard_type',
        'links_purchased_separately',
        'links_title',
        'name',
        'pcs',
        'price',
        'price_type',
        'price_view',
        'samples_title',
        'shipment_type',
        'status',
        'weight',
    );
    /**
     * Is requiredAttributeValid
     *
     * @param string $attrCode        string
     * @param array  $attributeParams array
     * @param array  $rowData         array
     *
     * @return bool
     */
    public function isRequiredAttributeValid($attrCode, array $attributeParams, array $rowData)
    {

        $doCheck = false;
        if ($attrCode == \Magento\CatalogImportExport\Model\Import\Product::COL_SKU) {
            $doCheck = true;
        } elseif ($attrCode == 'price') {
            $doCheck = false;
        } elseif ($attributeParams['is_required'] && $this->getRowScope($rowData) == \Magento\CatalogImportExport\Model\Import\Product::SCOPE_DEFAULT
            && $this->context->getBehavior() != \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE
        ) {
            $doCheck = true;
        }
        if (in_array($attrCode, $this->attributeIgnoreOnImports)) {
            return true;
        }
        return $doCheck ? isset($rowData[$attrCode]) && strlen(trim($rowData[$attrCode])) : true;
    }
}