<?php
/**
 * to install queue, use InstallData from amqp module
 */

namespace Riki\ThirdPartyImportExport\Setup;

use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Amqp\Model\Topology;

/**
 * @codeCoverageIgnore
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var Topology|\Magento\MysqlMq\Setup\InstallData
     */
    private $topology;

    /**
     * Constructor
     *
     * @param Topology $topology
     */
//    public function __construct(
//        \Magento\MysqlMq\Setup\InstallData $topology)
//    {
//        $this->topology = $topology;
//    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->getConnection()->delete($setup->getTable('queue'), ['name = ?' => 'inventory_qty_counter_queue']);
//        $this->topology->install($setup,$context);
    }
}
