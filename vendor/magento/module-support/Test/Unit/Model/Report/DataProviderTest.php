<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report;

use Magento\Support\Model\Report\DataProvider;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Support\Model\Report\Config;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;

/**
 * Class DataProviderTest
 */
class DataProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $collectionMock;

    /**
     * @var Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $reportConfigMock;

    /**
     * @var DataProvider
     */
    protected $model;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->reportConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionMock = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->collectionMock);

        $this->model = $this->objectManager->getObject(DataProvider::class, [
            'reportConfig' => $this->reportConfigMock,
            'collectionFactory' => $this->collectionFactoryMock,
        ]);
    }

    public function testGetData()
    {
        $groupNames = ['general', 'environment'];
        $expected = [null => ['general' => ['report_groups' => $groupNames]]];

        $this->reportConfigMock->expects($this->once())
            ->method('getGroupNames')
            ->willReturn($groupNames);

        $this->assertEquals($expected, $this->model->getData());
    }
}
