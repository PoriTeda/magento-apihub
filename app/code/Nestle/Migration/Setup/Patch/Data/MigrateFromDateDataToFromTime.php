<?php

namespace Nestle\Migration\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;

class MigrateFromDateDataToFromTime implements \Magento\Framework\Setup\Patch\DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }


    /**
     * Do Migration
     *
     * @return void
     * @throws \Exception
     */
    public function apply()
    {
        $connection = $this->moduleDataSetup->getConnection();
        $connection->beginTransaction();

        try {
            $this->migrateFromDateToFromTime();
            $connection->commit();
        } catch (\Exception $e) {
            $connection->rollBack();
            throw $e;
        }


    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }

    private function migrateFromDateToFromTime()
    {
        $connection = $this->moduleDataSetup->getConnection();


        $connection->update(
            $this->moduleDataSetup->getTable('salesrule'),
            [
                'from_time' => new \Zend_Db_Expr('CASE WHEN (from_date IS NOT NULL) THEN TIMESTAMP (from_date, TIME (from_time)) ELSE NULL END'),
                'to_time' => new \Zend_Db_Expr('CASE WHEN (to_date IS NOT NULL) THEN TIMESTAMP (to_date, TIME (to_time)) ELSE NULL END')
            ]
        );
    }
}