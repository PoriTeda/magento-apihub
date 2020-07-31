<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Model\Plugin\ResourceModel\Product;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\CatalogStaging\Model\Plugin\ResourceModel\Product\Collection;
use Magento\Staging\Model\VersionManager;

class CollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $metadataMock;

    /**
     * @var \Magento\CatalogStaging\Model\Plugin\ResourceModel\Product\Collection
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $versionManagerMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $metadataPoolMock = $this->getMockBuilder(\Magento\Framework\EntityManager\MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataMock = $this->getMockBuilder(\Magento\Framework\EntityManager\EntityMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerMock;
        $metadataPoolMock->expects($this->once())
            ->method('getMetadata')
            ->willReturn($this->metadataMock);
        $this->model = $objectManager->getObject(
            Collection::class,
            [
                'metadataPool' => $metadataPoolMock,
                'versionManager' => $this->versionManagerMock
            ]
        );
    }

    public function testBeforeJoinAttribute()
    {
        $collectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $alias = 'test_alias';
        $attribute = 'catalog_product/weight_attribute';
        $bind = 'entity_id';
        $filter = 'test_filter';
        $joinType = 'test_join';
        $storeId = 1;

        $this->metadataMock->expects($this->once())
            ->method('getLinkField')
            ->willReturn('row_id');

        $this->assertEquals(
            [$alias, $attribute, 'row_id', $filter, $joinType, $storeId],
            $this->model->beforeJoinAttribute(
                $collectionMock,
                $alias,
                $attribute,
                $bind,
                $filter,
                $joinType,
                $storeId
            )
        );
    }
}
