<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Test\Unit\Ui;

class ArchiveDataProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Default value for DataProvider
     */
    const DEFAULT_DATA_PROVIDER = 'default_data_provider';

    /**
     * Value for archive DataProvider
     */
    const ARCHIVE_DATA_PROVIDER = 'archive_data_provider';

    /**
     * @var \Magento\SalesArchive\Ui\ArchiveDataProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $archiveDataProvider;

    /**
     * @var \Magento\SalesArchive\Model\ResourceModel\Archive|\PHPUnit_Framework_MockObject_MockObject
     */
    private $archiveResourceModelMock;

    /**
     * @var \Magento\Framework\Api\Search\SearchCriteriaBuilder|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var \Magento\Framework\Api\Search\SearchCriteria|\PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaMock;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->searchCriteriaMock = $this->createMock(\Magento\Framework\Api\Search\SearchCriteria::class);
        $this->searchCriteriaBuilderMock = $this->createMock(
            \Magento\Framework\Api\Search\SearchCriteriaBuilder::class
        );
        $this->searchCriteriaBuilderMock->expects($this->once())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);
        $this->archiveResourceModelMock = $this->createMock(\Magento\SalesArchive\Model\ResourceModel\Archive::class);
        $this->archiveDataProvider = $objectManager->getObject(\Magento\SalesArchive\Ui\ArchiveDataProvider::class, [
            'name' => self::DEFAULT_DATA_PROVIDER,
            'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
            'archiveResourceModel' => $this->archiveResourceModelMock,
            'archiveDataSource' => self::ARCHIVE_DATA_PROVIDER
        ]);
    }

    /**
     * @param bool $isOrderInArchive
     * @param int $setRequestNameCount
     * @param \PHPUnit\Framework\Constraint\IsEqual[] $setRequestNameValues
     *
     * @dataProvider getSearchCriteriaDataProvider
     */
    public function testGetSearchCriteria($isOrderInArchive, $setRequestNameCount, array $setRequestNameValues)
    {
        $this->archiveResourceModelMock->expects($this->once())
            ->method('isOrderInArchive')
            ->willReturn($isOrderInArchive);

        $this->searchCriteriaMock->expects($this->exactly($setRequestNameCount))
            ->method('setRequestName')
            ->withConsecutive(...$setRequestNameValues);

        $this->archiveDataProvider->getSearchCriteria();
    }

    public function getSearchCriteriaDataProvider()
    {
        return [
            'Order in Archive' => [
                'isOrderInArchive' => true,
                'setRequestNameCount' => 2,
                'setRequestNameValues' => [
                    $this->equalTo(self::DEFAULT_DATA_PROVIDER),
                    $this->equalTo(self::ARCHIVE_DATA_PROVIDER)
                ]
            ],
            'Order not in Archive' => [
                'isOrderInArchive' => false,
                'setRequestNameCount' => 1,
                'setRequestNameValues' => [
                    $this->equalTo(self::DEFAULT_DATA_PROVIDER)
                ]
            ]
        ];
    }
}
