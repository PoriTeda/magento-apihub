<?php
namespace Riki\CatalogInventory\Setup;


class UpgradeData extends \Riki\Framework\Setup\Version\Data implements \Magento\Framework\Setup\UpgradeDataInterface
{
    /**
     * @var \Magento\Framework\App\Config\ConfigResource\ConfigInterface
     */
    protected $configResource;

    /**
     * UpgradeData constructor.
     *
     * @param \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configResource
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ConfigResource\ConfigInterface $configResource,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig
    ) {
        $this->configResource = $configResource;
        parent::__construct(
            $functionCache,
            $logger,
            $resourceConnection,
            $deploymentConfig
        );
    }

    public function version101()
    {
        // Wyomind_AdvancedInventory disable it, but it need for RIKI order flow.
        // I enable it again
        $this->configResource->saveConfig(
            \Magento\CatalogInventory\Model\Configuration::XML_PATH_CAN_SUBTRACT,
            1,
            'default',
            0
        );
    }
}