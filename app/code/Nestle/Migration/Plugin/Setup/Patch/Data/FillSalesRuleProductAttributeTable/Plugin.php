<?php


namespace Nestle\Migration\Plugin\Setup\Patch\Data\FillSalesRuleProductAttributeTable;


use Exception;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\SalesRule\Model\Rule;
use Nestle\Migration\Model\DataMigration;

class Plugin
{
    /**
     * @var CollectionFactory
     */
    private $ruleColletionFactory;

    public function __construct(
        CollectionFactory $ruleColletionFactory
    )
    {
        $this->ruleColletionFactory = $ruleColletionFactory;
    }

    public function beforeFillSalesRuleProductAttributeTable()
    {
        if (!is_null(DataMigration::$OUTPUT)) {
            $ruleCollection = $this->ruleColletionFactory->create();
            /** @var Rule $rule */
            foreach ($ruleCollection as $rule) {
                if (count($rule->getCustomerGroupIds()) == 0) {
                    try {
                        DataMigration::warning("fixing wrong rule data with id " . $rule->getId() . ". Rule doesn't have customer group ids. Fix by deleting the record. Please check this record.");
                        $rule->delete();
                    } catch (Exception $exception) {

                    }
                }
            }
        }
    }

    public function afterGetAliases($subject, $result)
    {
        return [
            "Magento\SalesRule\Setup\Patch\Data\FillSalesRuleProductAttributeTable"
        ];
    }
}
