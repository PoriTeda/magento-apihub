<?php

namespace Riki\CatalogImport\Plugin;

class ValidatorPlugin
{
    /**
     * @var \Riki\CatalogImport\Model\Import\Product
     */
    protected $catalogImportProduct;

    /**
     * ValidatorPlugin constructor.
     * @param \Riki\CatalogImport\Model\Import\Product $catalogImportProduct
     */
    public function __construct(
        \Riki\CatalogImport\Model\Import\Product $catalogImportProduct
    ) {
        $this->catalogImportProduct = $catalogImportProduct;
    }

    /**
     * @param $subject
     * @param $attrCode
     * @param array $attrParams
     * @param array $rowData
     * @return array
     */
    public function beforeIsAttributeValid($subject, $attrCode, array $attrParams, array $rowData)
    {
        if (in_array($attrCode, $this->catalogImportProduct->specialMultiSelectAttributes)) {
            $rowData = $this->catalogImportProduct->convertMultiSelectData($attrCode, $attrParams, $rowData);
        }
        return [$attrCode, $attrParams, $rowData];
    }
}
