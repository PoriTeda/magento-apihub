<?php


namespace Nestle\Migration\Setup\Patch\Schema;


use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;

class AddForeignKeyAmastyRewardsRuleCustomerGroup implements SchemaPatchInterface
{

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        ResourceConnection $resourceConnection
    )
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Get array of patches that have to be executed prior to this.
     *
     * example of implementation:
     *
     * [
     *      \Vendor_Name\Module_Name\Setup\Patch\Patch1::class,
     *      \Vendor_Name\Module_Name\Setup\Patch\Patch2::class
     * ]
     *
     * @return string[]
     */
    public static function getDependencies()
    {
        // TODO: Implement getDependencies() method.
        return [];
    }

    /**
     * Get aliases (previous names) for the patch.
     *
     * @return string[]
     */
    public function getAliases()
    {
        // TODO: Implement getAliases() method.
        return [];
    }

    /**
     * Run code inside patch
     * If code fails, patch must be reverted, in case when we are speaking about schema - than under revert
     * means run PatchInterface::revert()
     *
     * If we speak about data, under revert means: $transaction->rollback()
     *
     * @return $this
     */
    public function apply()
    {
        $adapter = $this->resourceConnection->getConnection("default");
//
//        $adapter->changeColumn(
//            $adapter->getTableName("customer_group"),
//            "customer_group_id",
//            "customer_group_id",
//            [
//                'type'     => Table::TYPE_INTEGER,
//                'nullable' => false,
//                'unsigned' => true
//            ]
//        );

        if (!$adapter->isTableExists("amasty_rewards_rule_customer_group")) {
            return $this;
        }

        $adapter->addForeignKey(
            $this->resourceConnection->getFkName(
                $adapter->getTableName("amasty_rewards_rule_customer_group"),
                "customer_group_id",
                $adapter->getTableName("customer_group"),
                "customer_group_id"
            ),
            $adapter->getTableName("amasty_rewards_rule_customer_group"),
            "customer_group_id",
            $adapter->getTableName("customer_group"),
            "customer_group_id"
        );
    }
}
