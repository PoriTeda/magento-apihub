<?php


namespace Nestle\Migration\Plugin\DB\Adapter\Pdo\Mysql;


use Nestle\Migration\Model\DataMigration;

class Plugin
{
    public function aroundInsertArray($subject, callable $process, $table, array $columns, array $data, $strategy = 0)
    {
        if (!is_null(DataMigration::$OUTPUT)) {
            if (count($data) == 0) {
                DataMigration::info("fixing insertArray wrong data");

                return 0;
            } else
                return $process($table, $columns, $data, $strategy);
        } else {
            return $process($table, $columns, $data, $strategy);
        }
    }
}
