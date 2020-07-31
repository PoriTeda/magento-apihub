<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableInventory\Test\Unit\Model\ResourceModel;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\ScalableInventory\Model\ResourceModel\QtyCounterProxy;

class QtyCounterProxyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\ScalableInventory\Model\ResourceModel\QtyCounter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $qtyCounter;

    /**
     * @var \Magento\CatalogInventory\Model\ResourceModel\Stock|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stockResource;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $scopeConfig;

    /**
     * @var \Magento\CatalogInventory\Api\StockRegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $stockRegistry;

    /**
     * @var QtyCounterProxy
     */
    private $resource;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->qtyCounter = $this->getMockBuilder(\Magento\ScalableInventory\Model\ResourceModel\QtyCounter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->stockResource = $this->getMockBuilder(\Magento\CatalogInventory\Model\ResourceModel\Stock::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->scopeConfig = $this->getMockBuilder(\Magento\Framework\App\Config\ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->stockRegistry = $this->getMockBuilder(\Magento\CatalogInventory\Api\StockRegistryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->resource = $objectManager->getObject(
            \Magento\ScalableInventory\Model\ResourceModel\QtyCounterProxy::class,
            [
                'qtyCounter' => $this->qtyCounter,
                'stockResource' => $this->stockResource,
                'scopeConfig' => $this->scopeConfig,
                'stockRegistry' => $this->stockRegistry,
            ]
        );
    }

    public function testCorrectItemsQty()
    {
        $items = [5269 => 12, 9462 => 31];
        $websiteId = 1;
        $operator = '+';

        $this->scopeConfig->expects($this->at(0))
            ->method('isSetFlag')
            ->with(\Magento\CatalogInventory\Model\Configuration::XML_PATH_BACKORDERS)
            ->willReturn(1);

        $this->scopeConfig->expects($this->at(1))
            ->method('isSetFlag')
            ->with(QtyCounterProxy::CONFIG_PATH_USE_DEFERRED_STOCK_UPDATE)
            ->willReturn(1);

        $stockItem5269 = $this->getMockBuilder(\Magento\CatalogInventory\Api\Data\StockItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getBackorders',
                'getUseConfigDeferredStockUpdate',
                'getDeferredStockUpdate'
            ])
            ->getMockForAbstractClass();

        $stockItem5269->expects($this->once())
            ->method('getBackorders')
            ->willReturn(0);
        $stockItem5269->expects($this->once())
            ->method('getDeferredStockUpdate')
            ->willReturn(0);
        $stockItem5269->expects($this->once())
            ->method('getUseConfigDeferredStockUpdate')
            ->willReturn(0);

        $stockItem9462 = $this->getMockBuilder(\Magento\CatalogInventory\Api\Data\StockItemInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBackorders', 'getUseConfigDeferredStockUpdate', 'getDeferredStockUpdate'])
            ->getMockForAbstractClass();
        $stockItem9462->expects($this->once())
            ->method('getBackorders')
            ->willReturn(1);
        $stockItem9462->expects($this->once())
            ->method('getDeferredStockUpdate')
            ->willReturn(0);
        $stockItem9462->expects($this->once())
            ->method('getUseConfigDeferredStockUpdate')
            ->willReturn(1);

        $this->stockRegistry->expects($this->exactly(2))
            ->method('getStockItem')
            ->willReturnMap([[5269, 1, $stockItem5269], [9462, 1, $stockItem9462]]);

        $this->qtyCounter->expects($this->once())
            ->method('correctItemsQty')
            ->with([9462 => 31], $websiteId, $operator);

        $this->stockResource->expects($this->once())
            ->method('correctItemsQty')
            ->with([5269 => 12], $websiteId, $operator);

        $this->resource->correctItemsQty($items, $websiteId, $operator);
    }
}
