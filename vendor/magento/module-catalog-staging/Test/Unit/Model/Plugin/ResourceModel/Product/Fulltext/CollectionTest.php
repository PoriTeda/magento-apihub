<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Model\Plugin\ResourceModel\Product\Fulltext;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\CatalogStaging\Model\Plugin\ResourceModel\Product\Fulltext\Collection;
use Magento\Staging\Model\VersionManager;
use Magento\Framework\DB\Select;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogStaging\Model\Plugin\ResourceModel\Product\Fulltext\Collection
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $versionManagerMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->metadataMock = $this->getMockBuilder(\Magento\Framework\EntityManager\EntityMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerMock;

        $this->model = $objectManager->getObject(
            Collection::class,
            [
                'versionManager' => $this->versionManagerMock
            ]
        );
    }

    public function testBeforeLoad()
    {
        $selectFromData = [
            'main_table' => [],
            'search_result' => ['joinType' => Select::INNER_JOIN],
            'tmp'
        ];
        $expectedSelectFromData = $selectFromData;
        $expectedSelectFromData['search_result']['joinType'] = Select::LEFT_JOIN;

        $collectionMock = $this->getMockBuilder(\Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $selectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock->expects($this->any())
            ->method('getPart')
            ->with(Select::FROM)
            ->willReturn($selectFromData);
        $selectMock->expects($this->once())
            ->method('setPart')
            ->with(Select::FROM)
            ->willReturn($expectedSelectFromData);

        $collectionMock->expects($this->any())
            ->method('getSelect')
            ->willReturn($selectMock);

        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn(true);

        $this->model->beforeLoad($collectionMock);
    }
}
