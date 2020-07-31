<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Test\Unit\Model\Block\Identifier;

class DataProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $collection;

    /**
     * @var \Magento\CmsStaging\Model\Page\Identifier\DataProvider
     */
    protected $model;

    protected function setUp()
    {
        $this->collection = $this->createMock(\Magento\Cms\Model\ResourceModel\Block\Collection::class);
        $collectionFactory = $this->createPartialMock(
            \Magento\Cms\Model\ResourceModel\Block\CollectionFactory::class,
            ['create']
        );
        $collectionFactory->expects($this->once())->method('create')->willReturn($this->collection);

        $this->model = new \Magento\Cms\Model\Block\DataProvider(
            'name',
            'primaryFieldName',
            'requestFieldName',
            $collectionFactory,
            $this->createMock(\Magento\Framework\App\Request\DataPersistorInterface::class)
        );
    }

    public function testGetData()
    {
        $blockId = 100;
        $blockData = ['key' => 'value'];
        $blockMock = $this->createMock(\Magento\Cms\Model\Block::class);
        $blockMock->expects($this->once())->method('getId')->willReturn($blockId);
        $blockMock->expects($this->once())->method('getData')->willReturn($blockData);

        $expectedResult = [$blockId => $blockData];
        $this->collection->expects($this->once())->method('getItems')->willReturn([$blockMock]);

        $this->assertEquals($expectedResult, $this->model->getData());
    }
}
