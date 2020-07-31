<?php
namespace Riki\Cookie\Setup;;

use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * @codeCoverageIgnore
 */
class UpgradeData implements UpgradeDataInterface
{
    protected $_config;

    protected $_storeRepository;

    /**
     * @param \Magento\Config\Model\ResourceModel\Config $config
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepositoryInterface
     */
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config  $config,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepositoryInterface
    )

    {
        $this->_config = $config ;
        $this->_storeRepository = $storeRepositoryInterface;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '0.1.1') < 0) {

            $stores = $this->_storeRepository->getList();

            /** @var \Magento\Store\Api\Data\StoreInterface $store */
            foreach($stores as $store){
                $this->_config->saveConfig('web/cookie/cookie_path',
                    '/' . $store->getCode() . '/',
                    'stores', $store->getId());
            }
        }

        if (version_compare($context->getVersion(), '0.1.2') < 0) {

            $stores = $this->_storeRepository->getList();

            /** @var \Magento\Store\Api\Data\StoreInterface $store */
            foreach($stores as $store){
                $this->_config->saveConfig('web/cookie/cookie_path',
                    '/' . $store->getCode(),
                    'stores', $store->getId());
            }
        }

        if (version_compare($context->getVersion(), '0.1.3') < 0) {

            $stores = $this->_storeRepository->getList();

            /** @var \Magento\Store\Api\Data\StoreInterface $store */
            foreach($stores as $store){
                $this->_config->deleteConfig('web/cookie/cookie_path', 'stores', $store->getId());
            }
        }

        $setup->endSetup();
    }
}
