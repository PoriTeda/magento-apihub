<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Model\Product\Identifier;

class DataProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogStaging\Model\Product\Identifier\DataProvider
     */
    private $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $collectionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $poolMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    protected function setUp()
    {
        $this->collectionMock = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);
        $collectionFactoryMock = $this->createPartialMock(
            \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class,
            ['create']
        );
        $collectionFactoryMock->expects($this->once())->method('create')->willReturn($this->collectionMock);
        $this->poolMock = $this->createMock(\Magento\Ui\DataProvider\Modifier\PoolInterface::class);
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->model = new \Magento\CatalogStaging\Model\Product\Identifier\DataProvider(
            'name',
            'primaryFieldName',
            'requestFieldName',
            $collectionFactoryMock,
            $this->poolMock,
            $this->requestMock
        );
    }

    public function testGetData()
    {
        $productId = 100;
        $productName = 'name';
        $storeId = 1;
        $productMock = $this->createMock(\Magento\Catalog\Model\Product::class);
        $productMock->expects($this->exactly(2))->method('getId')->willReturn($productId);
        $productMock->expects($this->once())->method('getName')->willReturn($productName);

        $this->collectionMock->expects($this->once())->method('getItems')->willReturn([$productMock]);
        $this->requestMock
            ->expects($this->once())
            ->method('getParam')
            ->with('store', \Magento\Store\Model\Store::DEFAULT_STORE_ID)
            ->willReturn($storeId);

        $expectedResult = [
            $productId => [
                'entity_id' => $productId,
                'name' => $productName,
                'store_id' => $storeId
            ]
        ];

        $this->assertEquals($expectedResult, $this->model->getData());
    }
}
