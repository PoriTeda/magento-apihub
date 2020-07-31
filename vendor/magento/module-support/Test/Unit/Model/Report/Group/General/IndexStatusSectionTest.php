<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\General;

class IndexStatusSectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Model\Report\Group\General\IndexStatusSection
     */
    protected $indexStatus;

    /**
     * @var \Magento\Indexer\Model\Indexer\CollectionFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexerFactoryMock;

    /**
     * @var \Magento\Indexer\Model\Indexer\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Framework\Mview\View|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $viewMock;

    /**
     * @var \Magento\Indexer\Model\Indexer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryProductsIndexerMock;

    /**
     * @var \Magento\Indexer\Model\Indexer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productCategoriesIndexerMock;

    /**
     * @var \Magento\Indexer\Model\Indexer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $catalogSearchIndexerMock;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $timeZoneMock;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    protected function setUp()
    {
        $this->objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->indexerFactoryMock = $this->getMockBuilder(\Magento\Indexer\Model\Indexer\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->collectionFactory = $this->getMockBuilder(\Magento\Indexer\Model\Indexer\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getItems'])
            ->getMock();

        $this->viewMock = $this->createMock(\Magento\Framework\Mview\View::class);
        $this->timeZoneMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);

        $this->categoryProductsIndexerMock = $this->getMockBuilder(\Magento\Indexer\Model\Indexer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getView', 'getTitle', 'getStatus', 'isValid', 'getLatestUpdated', 'getDescription'])
            ->getMock();
        $this->productCategoriesIndexerMock = $this->getMockBuilder(\Magento\Indexer\Model\Indexer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getView', 'getTitle', 'getStatus', 'isValid', 'getLatestUpdated', 'getDescription'])
            ->getMock();
        $this->catalogSearchIndexerMock = $this->getMockBuilder(\Magento\Indexer\Model\Indexer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getView', 'getTitle', 'getStatus', 'isValid', 'getLatestUpdated', 'getDescription'])
            ->getMock();

        $this->indexStatus = $this->objectManagerHelper->getObject(
            \Magento\Support\Model\Report\Group\General\IndexStatusSection::class,
            [
                'indexerFactory' => $this->indexerFactoryMock,
                'timeZone' => $this->timeZoneMock
            ]
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $categoryProductsTitle = 'Category Products';
        $categoryProductsDescription = 'Indexed category/products association';
        $productCategoriesTitle = 'Product Categories';
        $productCategoriesDescription = 'Indexed product/categories association';
        $catalogSearchTitle = 'Catalog Search';
        $catalogSearchDescription = 'Rebuild Catalog product fulltext search index';
        $invalidStatus = 'invalid';
        $validStatus = 'valid';
        $latestUpdatedDate = '2015-07-24 12:38:14';

        $expectedData = [
            \Magento\Support\Model\Report\Group\General\IndexStatusSection::REPORT_TITLE => [
                'headers' => ['Index', 'Status', 'Update Required', 'Updated At', 'Mode', 'Is Visible', 'Description'],
                'data' => [
                    [
                        'Category Products',
                        'invalid',
                        'Yes',
                        '2015-07-24 12:38:14',
                        'Update On Save',
                        'n/a',
                        'Indexed category/products association'
                    ],
                    [
                        'Product Categories',
                        'invalid',
                        'Yes',
                        '2015-07-24 12:38:14',
                        'Update On Save',
                        'n/a',
                        'Indexed product/categories association'
                    ],
                    [
                        'Catalog Search',
                        'valid',
                        'No',
                        '2015-07-24 12:38:14',
                        'Update On Save',
                        'n/a',
                        'Rebuild Catalog product fulltext search index'
                    ],
                ]
            ]
        ];

        $indexers = [
            $this->categoryProductsIndexerMock,
            $this->productCategoriesIndexerMock,
            $this->catalogSearchIndexerMock
        ];

        $this->indexerFactoryMock->expects($this->once())->method('create')->willReturn($this->collectionFactory);
        $this->collectionFactory->expects($this->once())->method('getItems')->willReturn($indexers);

        $this->categoryProductsIndexerMock->expects($this->once())->method('getView')->willReturn($this->viewMock);
        $this->viewMock->expects($this->atLeastOnce())->method('isEnabled')->willReturn(false);
        $this->categoryProductsIndexerMock->expects($this->once())->method('getTitle')->willReturn(
            $categoryProductsTitle
        );
        $this->categoryProductsIndexerMock->expects($this->once())->method('getStatus')->willReturn($invalidStatus);
        $this->categoryProductsIndexerMock->expects($this->once())->method('isValid')->willReturn(false);
        $this->categoryProductsIndexerMock->expects($this->atLeastOnce())->method('getLatestUpdated')->willReturn(
            $latestUpdatedDate
        );
        $this->categoryProductsIndexerMock->expects($this->once())->method('getDescription')->willReturn(
            $categoryProductsDescription
        );
        $this->timeZoneMock->expects($this->any())->method('formatDateTime')->willReturn($latestUpdatedDate);
        $this->productCategoriesIndexerMock->expects($this->once())->method('getView')->willReturn($this->viewMock);
        $this->productCategoriesIndexerMock->expects($this->once())->method('getTitle')->willReturn(
            $productCategoriesTitle
        );
        $this->productCategoriesIndexerMock->expects($this->once())->method('getStatus')->willReturn($invalidStatus);
        $this->productCategoriesIndexerMock->expects($this->once())->method('isValid')->willReturn(false);
        $this->productCategoriesIndexerMock->expects($this->atLeastOnce())->method('getLatestUpdated')->willReturn(
            $latestUpdatedDate
        );
        $this->productCategoriesIndexerMock->expects($this->once())->method('getDescription')->willReturn(
            $productCategoriesDescription
        );

        $this->catalogSearchIndexerMock->expects($this->once())->method('getView')->willReturn($this->viewMock);
        $this->catalogSearchIndexerMock->expects($this->once())->method('getTitle')->willReturn($catalogSearchTitle);
        $this->catalogSearchIndexerMock->expects($this->once())->method('getStatus')->willReturn($validStatus);
        $this->catalogSearchIndexerMock->expects($this->once())->method('isValid')->willReturn(true);
        $this->catalogSearchIndexerMock->expects($this->atLeastOnce())->method('getLatestUpdated')->willReturn(
            $latestUpdatedDate
        );
        $this->catalogSearchIndexerMock->expects($this->once())->method('getDescription')->willReturn(
            $catalogSearchDescription
        );

        $this->assertSame($expectedData, $this->indexStatus->generate());
    }
}
