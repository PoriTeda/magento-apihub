<?php


namespace Nestle\Migration\Model\DataMigration;


use Nestle\Migration\Model\DataMigration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TruncateLoggingTable extends AbstractDataMigration
{
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->truncateTable($input, $output);
        return parent::run($input, $output); // TODO: Change the autogenerated stub
    }

    private function truncateTable(InputInterface $input, $output)
    {
        if ($input->getOption(DataMigration::OPTION_MIGRATION_CLEAN_LOG_TABLE) !== false) {
            DataMigration::info("clean logging table");
            $adapter = $this->resourceConnection->getConnection("default");
            $adapter->truncateTable($adapter->getTableName("magento_logging_event"));
            $adapter->truncateTable($adapter->getTableName("magento_logging_event_changes"));
        }
    }
}
