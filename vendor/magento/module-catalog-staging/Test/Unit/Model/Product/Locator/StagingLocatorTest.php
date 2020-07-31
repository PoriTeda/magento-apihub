<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Model\Product\Locator;

use Magento\CatalogStaging\Model\Product\Locator\StagingLocator;

class StagingLocatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var string
     */
    private $requestFieldName;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $productRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $registryMock;

    /**
     * @var StagingLocator
     */
    private $locator;

    protected function setUp()
    {
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->versionManagerMock = $this->createMock(\Magento\Staging\Model\VersionManager::class);
        $this->updateRepositoryMock = $this->createMock(\Magento\Staging\Api\UpdateRepositoryInterface::class);
        $this->productRepositoryMock = $this->createMock(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $this->storeManagerMock = $this->createMock(\Magento\Store\Model\StoreManagerInterface::class);
        $this->registryMock = $this->createMock(\Magento\Framework\Registry::class);
        $this->requestFieldName = 'fieldName';

        $this->locator = new StagingLocator(
            $this->registryMock,
            $this->requestMock,
            $this->versionManagerMock,
            $this->updateRepositoryMock,
            $this->productRepositoryMock,
            $this->storeManagerMock,
            $this->requestFieldName
        );
    }

    public function testGetProductRetrievesProductFromRegistryIfPresent()
    {
        $entityId = 1;
        $productMock = $this->createMock(\Magento\Catalog\Api\Data\ProductInterface::class);

        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->will($this->returnValueMap([
                ['update_id', null, null],
                [$this->requestFieldName, null, $entityId],
            ]));

        $this->registryMock->expects($this->once())
            ->method('registry')
            ->with('current_product')
            ->willReturn($productMock);
        $this->assertEquals($productMock, $this->locator->getProduct());
    }

    public function testGetProductRetrievesProductFromRepositoryIfProductIsNotInRegistry()
    {
        $entityId = 1;
        $storeId = 1;
        $storeMock = $this->createMock(\Magento\Store\Api\Data\StoreInterface::class);
        $storeMock->expects($this->any())->method('getId')->willReturn($storeId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->with($storeId)->willReturn($storeMock);

        $productMock = $this->createMock(\Magento\Catalog\Api\Data\ProductInterface::class);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->will($this->returnValueMap([
                ['update_id', null, null],
                [$this->requestFieldName, null, $entityId],
                ['store', 0, $storeId],
            ]));

        $this->productRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($entityId, true, $storeId, false)
            ->willReturn($productMock);

        $this->assertEquals($productMock, $this->locator->getProduct());
    }

    public function testGetStoreRetrievesStoreFromRegistryIfPresent()
    {
        $storeMock = $this->createMock(\Magento\Store\Api\Data\StoreInterface::class);

        $this->registryMock->expects($this->once())
            ->method('registry')
            ->with('current_store')
            ->willReturn($storeMock);

        $this->assertEquals($storeMock, $this->locator->getStore());
    }

    public function testGetStoreRetrievesStoreFromStoreManagerIfStoreIsNotInRegistry()
    {
        $storeId = 1;
        $storeMock = $this->createMock(\Magento\Store\Api\Data\StoreInterface::class);
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->with('store', 0)
            ->willReturn($storeId);
        $this->storeManagerMock->expects($this->any())->method('getStore')->with($storeId)->willReturn($storeMock);

        $this->assertEquals($storeMock, $this->locator->getStore());
    }
}
