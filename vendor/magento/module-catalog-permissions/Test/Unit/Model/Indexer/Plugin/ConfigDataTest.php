<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ConfigDataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\App\CacheInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $coreCacheMock;

    /**
     * @var \Magento\Framework\Indexer\IndexerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexerMock;

    /**
     * @var \Magento\CatalogPermissions\App\ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $appConfigMock;

    /**
     * @var \Magento\Config\Model\Config\Loader|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configLoaderMock;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \Closure|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $closureMock;

    /**
     * @var \Magento\Config\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backendConfigMock;

    /**
     * @var \Magento\CatalogPermissions\Model\Indexer\Plugin\ConfigData
     */
    protected $configData;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexerRegistryMock;

    protected function setUp()
    {
        $this->coreCacheMock = $this->createPartialMock(\Magento\Framework\App\Cache::class, ['clean']);
        $this->appConfigMock = $this->createPartialMock(
            \Magento\CatalogPermissions\App\Backend\Config::class,
            ['isEnabled']
        );
        $this->indexerMock = $this->createPartialMock(\Magento\Indexer\Model\Indexer::class, ['getId', 'invalidate']);
        $this->configLoaderMock = $this->createPartialMock(
            \Magento\Config\Model\Config\Loader::class,
            ['getConfigByPath']
        );
        $this->storeManagerMock = $this->createPartialMock(
            \Magento\Store\Model\StoreManager::class,
            ['getStore', 'getWebsite']
        );
        $backendConfigMock = $this->backendConfigMock = $this->createPartialMock(
            \Magento\Config\Model\Config::class,
            ['getStore', 'getWebsite', 'getSection']
        );
        $this->closureMock = function () use ($backendConfigMock) {
            return $backendConfigMock;
        };

        $this->indexerRegistryMock = $this->createPartialMock(
            \Magento\Framework\Indexer\IndexerRegistry::class,
            ['get']
        );

        $this->configData = new \Magento\CatalogPermissions\Model\Indexer\Plugin\ConfigData(
            $this->coreCacheMock,
            $this->appConfigMock,
            $this->indexerRegistryMock,
            $this->configLoaderMock,
            $this->storeManagerMock
        );
    }

    public function testAroundSaveWithoutChanges()
    {
        $section = 'test';
        $this->backendConfigMock->expects($this->exactly(2))->method('getStore')->will($this->returnValue(false));
        $this->backendConfigMock->expects($this->exactly(2))->method('getWebsite')->will($this->returnValue(false));
        $this->backendConfigMock->expects($this->exactly(2))->method('getSection')->will($this->returnValue($section));
        $this->configLoaderMock->expects(
            $this->exactly(2)
        )->method(
            'getConfigByPath'
        )->with(
            $section . '/magento_catalogpermissions',
            'default',
            0,
            false
        )->will(
            $this->returnValue(['test' => 1])
        );
        $this->appConfigMock->expects($this->never())->method('isEnabled');

        $this->indexerRegistryMock->expects($this->never())->method('get');

        $this->configData->aroundSave($this->backendConfigMock, $this->closureMock);
    }

    public function testAroundSaveIndexerTurnedOff()
    {
        $section = 'test';
        $storeId = 5;

        $store = $this->getStore();
        $store->expects($this->exactly(2))->method('getId')->will($this->returnValue($storeId));
        $this->backendConfigMock->expects($this->exactly(4))->method('getStore')->will($this->returnValue($store));
        $this->storeManagerMock->expects($this->exactly(2))->method('getStore')->will($this->returnValue($store));

        $this->backendConfigMock->expects($this->never())->method('getWebsite');

        $this->backendConfigMock->expects($this->exactly(2))->method('getSection')->will($this->returnValue($section));
        $this->prepareConfigLoader($section, $storeId, 'stores');

        $this->appConfigMock->expects($this->once())->method('isEnabled')->will($this->returnValue(false));
        $this->coreCacheMock->expects($this->never())->method('clean');

        $this->configData->aroundSave($this->backendConfigMock, $this->closureMock);
    }

    public function testAroundSaveIndexerTurnedOn()
    {
        $section = 'test';
        $websiteId = 20;

        $store = $this->getStore();
        $store->expects($this->exactly(2))->method('getId')->will($this->returnValue($websiteId));
        $this->backendConfigMock->expects($this->exactly(4))->method('getWebsite')->will($this->returnValue($store));
        $this->storeManagerMock->expects($this->exactly(2))->method('getWebsite')->will($this->returnValue($store));

        $this->storeManagerMock->expects($this->never())->method('getStore');

        $this->backendConfigMock->expects($this->exactly(2))->method('getStore');

        $this->backendConfigMock->expects($this->exactly(2))->method('getSection')->will($this->returnValue($section));

        $this->prepareConfigLoader($section, $websiteId, 'websites');

        $this->appConfigMock->expects($this->once())->method('isEnabled')->will($this->returnValue(true));

        $this->coreCacheMock->expects(
            $this->once()
        )->method(
            'clean'
        )->with(
            [
                \Magento\Catalog\Model\Category::CACHE_TAG,
                \Magento\Framework\App\Cache\Type\Block::CACHE_TAG,
                \Magento\Framework\App\Cache\Type\Layout::CACHE_TAG
            ]
        );

        $this->indexerMock->expects($this->exactly(2))->method('invalidate');

        $this->indexerRegistryMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$this->equalTo(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)],
                [$this->equalTo(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)]
            )
            ->willReturn($this->indexerMock);

        $this->configData->aroundSave($this->backendConfigMock, $this->closureMock);
    }

    /**
     * @return \Magento\Store\Model\Store|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getStore()
    {
        $store = $this->createPartialMock(\Magento\Store\Model\Store::class, ['getId', '__wakeup']);
        return $store;
    }

    /**
     * @return \Magento\Store\Model\Website|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWebsite()
    {
        $website = $this->createPartialMock(\Magento\Store\Model\Website::class, ['getId', '__wakeup']);
        return $website;
    }

    /**
     * @param string $section
     * @param int $objectId
     * @param string $type
     */
    protected function prepareConfigLoader($section, $objectId, $type)
    {
        $counter = 0;
        $this->configLoaderMock->expects(
            $this->exactly(2)
        )->method(
            'getConfigByPath'
        )->with(
            $section . '/magento_catalogpermissions',
            $type,
            $objectId,
            false
        )->will(
            $this->returnCallback(
                function () use (&$counter) {
                    return ++$counter % 2 ? ['test' => 1] : ['test' => 2];
                }
            )
        );
    }
}
