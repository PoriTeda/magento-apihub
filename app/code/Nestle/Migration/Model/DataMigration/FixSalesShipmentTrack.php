<?php


namespace Nestle\Migration\Model\DataMigration;


use Nestle\Migration\Model\DataMigration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Currently, table `sales_shipment_track` has indexes for column `track_number` but in magento 2.3+ that column will be modified to `text` type.
 * Mysql can not index for text type. => we need remove index.
 *
 * Class FixSalesShipmentTrack
 * @package Nestle\Migration\Model\DataMigration
 */
class FixSalesShipmentTrack extends AbstractDataMigration
{
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->fixTrackNumberIndex($input, $output);
        return parent::run($input, $output); // TODO: Change the autogenerated stub
    }

    public function fixTrackNumberIndex(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->sharding->getResources() as $resource) {
            $connection = $this->resourceConnection->getConnection($resource);
            if ($connection->isTableExists("sales_shipment_track")) {
                $indexes = $connection->getIndexList("sales_shipment_track");
                foreach ($indexes as $index) {
                    if (isset($index["COLUMNS_LIST"][0]) && $index["COLUMNS_LIST"][0] == "track_number") {
                        $keyName = $index["KEY_NAME"];
                        DataMigration::info(`removing index in table \`sales_shipment_track\` key name $keyName`);
                        $connection->dropIndex("sales_shipment_track", $keyName);
                    }
                }
            }
        }
    }
}