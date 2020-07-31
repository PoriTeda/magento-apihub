<?php


namespace Nestle\Migration\Plugin\Magento\Framework\Setup\Patch\PatchHistory;


use Magento\Framework\App\ResourceConnection;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Setup\Patch\PatchFactory;
use Magento\Framework\Setup\Patch\PatchHistory;

/**
 * Class Plugin
 * Support add aliases name to patch_list table.
 * Magento check patch which is applied by finding in patch_list table. So we need add alias name into it.
 *
 * @package Nestle\Migration\Plugin\Magento\Framework\Setup\Patch\PatchHistory
 */
class Plugin
{
    /**
     * @var PatchFactory
     */
    private $patchFactory;
    /**
     * @var \Magento\Framework\Setup\SchemaSetupInterface
     */
    private $schemaSetup;
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * Plugin constructor.
     * @param PatchFactory $patchFactory
     * @param \Magento\Framework\Setup\SchemaSetupInterface $schemaSetup
     * @param ObjectManagerInterface $objectManager
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        PatchFactory $patchFactory,
        \Magento\Framework\Setup\SchemaSetupInterface $schemaSetup,
        ObjectManagerInterface $objectManager,
        ResourceConnection $resourceConnection)
    {
        $this->patchFactory = $patchFactory;
        $this->schemaSetup = $schemaSetup;
        $this->resourceConnection = $resourceConnection;
        $this->objectManager = $objectManager;
    }

    /**
     * @param $subject
     * @param callable $process
     * @param $patchName
     */
    public function aroundFixPatch($subject, callable $process, $patchName)
    {
        $process($patchName);

        $instance = $this->objectManager->get($patchName);

        try {
            foreach ($instance->getAliases() as $alias) {
                $adapter = $this->resourceConnection->getConnection();
                $adapter->insert($this->resourceConnection->getTableName(PatchHistory::TABLE_NAME), [PatchHistory::CLASS_NAME => $alias]);
            }
        } catch (\Exception $exception) {

        }
    }
}
