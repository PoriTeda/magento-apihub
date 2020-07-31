<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Test\Unit\Model\Page\Identifier;

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
        $this->collection = $this->createMock(\Magento\Cms\Model\ResourceModel\Page\Collection::class);
        $collectionFactory = $this->createPartialMock(
            \Magento\Cms\Model\ResourceModel\Page\CollectionFactory::class,
            ['create']
        );
        $collectionFactory->expects($this->once())->method('create')->willReturn($this->collection);

        $this->model = new \Magento\CmsStaging\Model\Page\Identifier\DataProvider(
            'name',
            'primaryFieldName',
            'requestFieldName',
            $collectionFactory
        );
    }

    public function testGetData()
    {
        $pageId = 100;
        $pageTitle = 'title';
        $pageMock = $this->createMock(\Magento\Cms\Model\Page::class);
        $pageMock->expects($this->exactly(2))->method('getId')->willReturn($pageId);
        $pageMock->expects($this->once())->method('getTitle')->willReturn($pageTitle);

        $expectedResult = [
            $pageId => [
                'page_id' => $pageId,
                'title' => $pageTitle
            ]
        ];
        $this->collection->expects($this->once())->method('getItems')->willReturn([$pageMock]);

        $this->assertEquals($expectedResult, $this->model->getData());
    }
}
