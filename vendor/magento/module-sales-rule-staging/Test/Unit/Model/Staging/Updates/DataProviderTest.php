<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesRuleStaging\Test\Unit\Model\Staging\Updates;

class DataProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SalesRuleStaging\Model\Staging\Updates\DataProvider
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

    /**
     * Setup the test
     */
    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);

        $className = \Magento\SalesRule\Model\ResourceModel\Rule\Collection::class;
        $this->collectionMock = $this->createMock($className);

        $className = \Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory::class;
        $collectionFactoryMock = $this->createPartialMock($className, ['create']);
        $collectionFactoryMock->expects($this->once())->method('create')->willReturn($this->collectionMock);

        $className = \Magento\SalesRuleStaging\Model\Staging\Updates\DataProvider::class;
        $this->model = $objectManager->getObject(
            $className,
            [
                'name' => 'myName',
                'primaryFieldName' => 'myPrimaryFieldName',
                'requestFieldName' => 'myRequestFieldName',
                'request' => $this->requestMock,
                'collectionFactory' => $collectionFactoryMock
            ]
        );
    }

    /**
     * test the getData() method when no updateId is provided
     */
    public function testGetDataIfUpdateIdIsNull()
    {
        $expectedResult = [
            'totalRecords' => 0,
            'items' => []
        ];

        $this->requestMock->expects($this->once())->method('getParam')->with('update_id')->willReturn(null);

        $this->assertEquals($expectedResult, $this->model->getData());
    }

    /**
     * test the getData() method with a valid updateId
     */
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
