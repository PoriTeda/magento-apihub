<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Test\Unit\Model\Staging\Updates;

class DataProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogRuleStaging\Model\Staging\Updates\DataProvider
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $collectionMock;

    protected function setUp()
    {
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->collectionMock = $this->createMock(\Magento\CatalogRule\Model\ResourceModel\Grid\Collection::class);
        $collectionFactoryMock = $this->createPartialMock(
            \Magento\CatalogRule\Model\ResourceModel\Grid\CollectionFactory::class,
            ['create']
        );
        $collectionFactoryMock->expects($this->once())->method('create')->willReturn($this->collectionMock);

        $this->model = new \Magento\CatalogRuleStaging\Model\Staging\Updates\DataProvider(
            'name',
            'primaryFieldName',
            'requestFieldName',
            $this->requestMock,
            $collectionFactoryMock
        );
    }

    public function testGetDataIfUpdateIdIsNull()
    {
        $this->requestMock->expects($this->once())->method('getParam')->with('update_id')->willReturn(null);
        $expectedResult = [
            'totalRecords' => 0,
            'items' => []
        ];
        $this->assertEquals($expectedResult, $this->model->getData());
    }

    public function testGetData()
    {
        $updateId = 10;
        $expectedResult = [
            'totalRecords' => 1,
            'items' => [
                'item' => 'value'
            ]
        ];

        $this->requestMock->expects($this->once())->method('getParam')->with('update_id')->willReturn($updateId);

        $selectMock = $this->createMock(\Magento\Framework\DB\Select::class);
        $selectMock->expects($this->once())->method('setPart')->with('disable_staging_preview', true)->willReturnSelf();
        $selectMock->expects($this->once())->method('where')->with('created_in = ?', $updateId)->willReturnSelf();

        $this->collectionMock->expects($this->once())->method('getSelect')->willReturn($selectMock);
        $this->collectionMock->expects($this->once())->method('toArray')->willReturn($expectedResult);

        $this->assertEquals($expectedResult, $this->model->getData());
    }
}
