<?php


namespace Nestle\Migration\Plugin\Declaration\Schema\Operations;


use Magento\Framework\Setup\Declaration\Schema\ElementHistory;
use Nestle\Migration\Model\DataMigration;

class AddComplexElementPlugin
{
    public function aroundDoOperation($subject, callable $proceed, ElementHistory $elementHistory)
    {
        if (!is_null(DataMigration::$OUTPUT)) {
            $table = $elementHistory->getNew() != null ? $elementHistory->getNew()->getName() : null;
            if (in_array($table, [
                "CATRULE_PRD_PRICE_RULE_DATE_WS_ID_CSTR_GROUP_ID_PRD_ID",
                "UNQ_EAA51B56FF092A0DCB795D1CEF812B7B"
            ])) {
                DataMigration::info("fixing wrong constrain " . $table);

                return [];
            } else {
                return $proceed($elementHistory);
            }
        } else {
            return $proceed($elementHistory);
        }
    }
}
