<?php


namespace Nestle\Migration\Model;


use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Setup\Declaration\Schema\DryRunLogger;
use Magento\Framework\Setup\Declaration\Schema\Sharding;
use Nestle\Migration\Model\DataMigration\AbstractDataMigration;
use Nestle\Migration\Model\DataMigration\CleanData;
use Nestle\Migration\Model\DataMigration\DuplicateTable;
use Nestle\Migration\Model\DataMigration\FixColumnType;
use Nestle\Migration\Model\DataMigration\FixConstrain;
use Nestle\Migration\Model\DataMigration\FixSalesShipmentTrack;
use Nestle\Migration\Model\DataMigration\TruncateLoggingTable;
use Nestle\Migration\Model\DataMigration\UpdateConfigData;
use Nestle\Migration\Model\DataMigration\UpdateManufactureAttribute;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DataMigration
 * @package Nestle\Migration\Model
 */
class DataMigration
{

    public static $IS_DEVELOPMENT = false;

    public static $IS_SUPPORT_MEMORY_ENGINE = false;

    public const OPTION_MIGRATION_CLEAN_DATA = "migration-clean-data";
    public const OPTION_MIGRATION_CLEAN_LOG_TABLE = "migration-clean-log-table";
    public const OPTION_MIGRATION_FIX_CONFIGURABLE_PRODUCT = "migration-fix-configurable";

    /**
     *
     */
    const DATA_MIGRATION = [
        DuplicateTable::class,
        CleanData::class,
        UpdateConfigData::class,
        TruncateLoggingTable::class,
        UpdateManufactureAttribute::class,
        FixColumnType::class,
        FixConstrain::class,
        FixSalesShipmentTrack::class
    ];
    /**
     * @var ObjectManager
     */
    private $objectManager;
    /**
     * @var Sharding
     */
    private $sharding;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var InputInterface
     */
    public static $INPUT = null;
    /**
     * @var OutputInterface
     */
    public static $OUTPUT = null;

    /**
     * DataMigration constructor.
     * @param ObjectManagerInterface $objectManager
     * @param Sharding $sharding
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Sharding $sharding,
        ResourceConnection $resourceConnection)
    {
        $this->sharding = $sharding;
        $this->resourceConnection = $resourceConnection;
        $this->objectManager = $objectManager;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function run(InputInterface $input, OutputInterface $output)
    {
        DataMigration::$INPUT = $input;
        DataMigration::$OUTPUT = $output;

        $this->startSetupForAllConnections();

        foreach (self::DATA_MIGRATION as $class) {
            /** @var AbstractDataMigration $instance */
            $instance = $this->objectManager->create($class);
            $instance->run($input, $output);
        }
        $this->endSetupForAllConnections();
//        die;
    }

    /**
     * In order to successfully run all operations we need to start setup for all
     * connections first.
     *
     * @return void
     */
    private function startSetupForAllConnections()
    {
        foreach ($this->sharding->getResources() as $resource) {
            $this->resourceConnection->getConnection($resource)
                                     ->startSetup();
            $this->resourceConnection->getConnection($resource)
                                     ->query('SET UNIQUE_CHECKS=0');
//            $this->resourceConnection->getConnection($resource)->query("SET SQL_MODE=''");
            $this->resourceConnection->getConnection($resource)->query("SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0");
//            $this->resourceConnection->getConnection($resource)->query("SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO'");
        }
    }

    /**
     * In order to revert previous state we need to end setup for all connections
     * connections first.
     *
     * @return void
     */
    private function endSetupForAllConnections()
    {
        foreach ($this->sharding->getResources() as $resource) {
            $this->resourceConnection->getConnection($resource)
                                     ->endSetup();
        }
    }

    public static function info($mess)
    {
        if (self::$OUTPUT != null) {
            self::$OUTPUT->writeln("<info>>>Migration Process: " . $mess . "</info>");
        }
    }

    public static function warning($mess)
    {
        if (self::$OUTPUT != null) {
            self::$OUTPUT->writeln("<error>>>Migration Process: " . $mess . "</error>");
        }
    }

    public static function addCommandCustomOption(&$options)
    {
        array_push($options, ...[
            new InputOption(
                self::OPTION_MIGRATION_CLEAN_DATA,
                null,
                InputOption::VALUE_OPTIONAL,
                'clean customer and order data',
                false
            ),
            new InputOption(
                self::OPTION_MIGRATION_CLEAN_LOG_TABLE,
                null,
                InputOption::VALUE_OPTIONAL,
                'clean logging table',
                false
            ),
            new InputOption(
                self::OPTION_MIGRATION_FIX_CONFIGURABLE_PRODUCT,
                null,
                InputOption::VALUE_OPTIONAL,
                'clean fix incompatible configurable patch',
                false
            )
        ]);

    }
}
