<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VersionsCms\Test\Unit\Model\ResourceModel\Hierarchy\Node;

use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection;

/**
 * Class CollectionTest
 * @package Magento\VersionsCms\Test\Unit\Model\ResourceModel\Hierarchy\Node
 */
class CollectionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Collection
     */
    private $collection;

    /**
     * @var MetadataPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataPoolMock;

    /**
     * @var AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionMock;

    /**
     * @var Select|\PHPUnit_Framework_MockObject_MockObject
     */
    private $selectMock;

    /**
     * @var AbstractDb|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resourceMock;

    public function setUp()
    {
        $objectManagerHelper = new ObjectManager($this);

        $this->metadataPoolMock = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->setMethods(['getMetadata'])
            ->getMock();

        $metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $metadataMock->expects($this->any())
            ->method('getLinkField')
            ->willReturn('id');

        $this->metadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->with(PageInterface::class)
            ->willReturn($metadataMock);

        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->connectionMock->expects($this->any())
            ->method('select')
            ->willReturn($this->selectMock);

        $this->resourceMock = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceMock->expects($this->any())
            ->method('getConnection')
            ->willReturn($this->connectionMock);

        $this->collection = $objectManagerHelper->getObject(
            Collection::class,
            [
                'resource' => $this->resourceMock,
                'metadataPool' => $this->metadataPoolMock
            ]
        );
    }

    public function testAddStoreFilter()
    {
        $this->collection->setFlag('cms_page_in_stores_data_joined', true);
        $this->selectMock->expects($this->once())->method('joinLeft')->with(
            ['cmsps' => $this->collection->getTable('cms_page_store')],
            'cmsps.' . 'id' . ' = main_table.page_id'
        )->willReturnSelf();
        $this->selectMock->expects($this->once())->method('where')->with(
            'cmsps.store_id IN (?) OR cmsps.store_id IS NULL',
            [0, 1]
        )->willReturnSelf();
        $this->selectMock->expects($this->once())->method('having')->with(
            'main_table.page_id IS NULL OR page_in_stores IS NOT NULL'
        )->willReturnSelf();
        $this->collection->addStoreFilter(1, true);
    }
}
