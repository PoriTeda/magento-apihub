<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableInventory\Test\Unit\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Test\Unit\Ui\DataProvider\Product\Form\Modifier\AbstractModifierTest;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\Store\Model\Store;
use Magento\ScalableInventory\Ui\DataProvider\Product\Form\Modifier\ScalableInventory;
use Magento\CatalogInventory\Api\StockConfigurationInterface;

/**
 * Class AdvancedInventoryTest
 */
class ScalableInventoryTest extends AbstractModifierTest
{
    /**
     * @var StockRegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $stockRegistryMock;

    /**
     * @var StockItemInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $stockItemMock;

    /**
     * @var Store|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeMock;

    /**
     * @var StockConfigurationInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $stockConfigMock;

    protected function setUp()
    {
        parent::setUp();
        $this->stockRegistryMock = $this->getMockBuilder(StockRegistryInterface::class)
            ->setMethods(['getStockItem'])
            ->getMockForAbstractClass();
        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->stockItemMock = $this->getMockBuilder(StockItemInterface::class)
            ->setMethods(['getDeferredStockUpdate', 'getUseConfigDeferredStockUpdate'])
            ->getMockForAbstractClass();
        $this->stockConfigMock = $this->getMockBuilder(StockConfigurationInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->stockRegistryMock->expects($this->any())
            ->method('getStockItem')
            ->willReturn($this->stockItemMock);
        $this->productMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);
    }

    /**
     * {@inheritdoc}
     */
    protected function createModel()
    {
        return $this->objectManager->getObject(ScalableInventory::class, [
            'locator' => $this->locatorMock,
            'stockRegistry' => $this->stockRegistryMock,
            'stockConfiguration' => $this->stockConfigMock
        ]);
    }

    public function testModifyMeta()
    {
        $this->assertNotEmpty($this->getModel()->modifyMeta(['meta_key' => 'meta_value']));
    }

    public function testModifyData()
    {
        $modelId = 1;

        $this->productMock->expects($this->any())
            ->method('getId')
            ->willReturn($modelId);
        $this->stockItemMock->expects($this->once())->method('getDeferredStockUpdate')->willReturn(1);
        $this->stockItemMock->expects($this->once())->method('getUseConfigDeferredStockUpdate')->willReturn(1);
        $this->stockConfigMock->expects($this->never())->method('getDefaultConfigValue');

        $this->assertArrayHasKey($modelId, $this->getModel()->modifyData([]));
    }

    public function testModifyDataWithDefaultValue()
    {
        $modelId = 1;

        $this->productMock->expects($this->any())
            ->method('getId')
            ->willReturn($modelId);
        $this->stockItemMock->expects($this->once())->method('getDeferredStockUpdate')->willReturn(null);
        $this->stockItemMock->expects($this->once())->method('getUseConfigDeferredStockUpdate')->willReturn(null);
        $this->stockConfigMock->expects($this->exactly(2))->method('getDefaultConfigValue');

        $this->assertArrayHasKey($modelId, $this->getModel()->modifyData([]));
    }
}
