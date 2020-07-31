<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\SalesArchive\Test\Unit\Model;

/**
 * Class ArchiveTest
 */
class ArchiveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\SalesArchive\Model\Archive
     */
    protected $archive;

    /**
     * @var \Magento\SalesArchive\Model\ResourceModel\Archive|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceArchive;

    /**
     * @var \Magento\Framework\Event\ManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventManager;

    /**
     * @var String
     */
    protected $archiveClassName = \Magento\SalesArchive\Model\Archive::class;

    protected function setUp()
    {
        $this->resourceArchive = $this->createMock(\Magento\SalesArchive\Model\ResourceModel\Archive::class);
        $this->eventManager = $this->createMock(\Magento\Framework\Event\Manager::class);

        $this->archive = new \Magento\SalesArchive\Model\Archive($this->resourceArchive, $this->eventManager);
    }

    public function testUpdateGridRecords()
    {
        $archiveEntity = 'orders';
        $ids = [100021, 100023, 100054];
        $this->resourceArchive->expects($this->once())
            ->method('updateGridRecords')
            ->with($this->equalTo($this->archive), $archiveEntity, $this->equalTo($ids));
        $result = $this->archive->updateGridRecords($archiveEntity, $ids);
        $this->assertInstanceOf($this->archiveClassName, $result);
    }

    public function testGetIdsInArchive()
    {
        $archiveEntity = 'orders';
        $ids = [100021, 100023, 100054];
        $relatedIds = [001, 003, 004];
        $this->resourceArchive->expects($this->once())
            ->method('getIdsInArchive')
            ->with($archiveEntity, $this->equalTo($ids))
            ->will($this->returnValue($relatedIds));
        $result = $this->archive->getIdsInArchive($archiveEntity, $ids);
        $this->assertEquals($relatedIds, $result);
    }

    public function testGetRelatedIds()
    {
        $archiveEntity = 'orders';
        $ids = [100021, 100023, 100054];
        $relatedIds = [001, 003, 004];
        $this->resourceArchive->expects($this->once())
            ->method('getRelatedIds')
            ->with($archiveEntity, $this->equalTo($ids))
            ->will($this->returnValue($relatedIds));
        $result = $this->archive->getRelatedIds($archiveEntity, $ids);
        $this->assertEquals($relatedIds, $result);
    }

    public function testArchiveOrders()
    {
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';
        $order = 'order_id';

        $this->resourceArchive->expects($this->once())
            ->method('getOrderIdsForArchiveExpression')
            ->will($this->returnValue($ids));

        $this->resourceArchive->expects($this->at(2))
            ->method('moveToArchive')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::ORDER), $entity, $this->equalTo($ids))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(3))
            ->method('moveToArchive')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::INVOICE), $order, $this->equalTo($ids))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(4))
            ->method('moveToArchive')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::SHIPMENT), $order, $this->equalTo($ids))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(5))
            ->method('moveToArchive')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::CREDITMEMO), $order, $this->equalTo($ids))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(6))
            ->method('removeFromGrid')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::ORDER), $entity, $this->equalTo($ids))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(7))
            ->method('removeFromGrid')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::INVOICE), $order, $this->equalTo($ids))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(8))
            ->method('removeFromGrid')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::SHIPMENT), $order, $this->equalTo($ids))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(9))
            ->method('removeFromGrid')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::CREDITMEMO), $order, $this->equalTo($ids))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(10))
            ->method('commit')
            ->will($this->returnSelf());

        $event = 'magento_salesarchive_archive_archive_orders';
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with($event, $this->equalTo(['order_ids' => $ids]));

        $result = $this->archive->archiveOrders();
        $this->assertInstanceOf($this->archiveClassName, $result);
    }

    /**
     * @expectedException \Exception
     */
    public function testArchiveOrdersException()
    {
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';

        $this->resourceArchive->expects($this->once())
            ->method('getOrderIdsForArchiveExpression')
            ->will($this->returnValue($ids));
        $this->resourceArchive->expects($this->at(2))
            ->method('moveToArchive')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::ORDER), $entity, $this->equalTo($ids))
            ->will($this->throwException(new \Exception()));
        $this->resourceArchive->expects($this->at(3))
            ->method('rollback')
            ->will($this->returnSelf());

        $result = $this->archive->archiveOrders();
        $this->assertInstanceOf('Exception', $result);
    }

    public function testArchiveOrdersById()
    {
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';
        $order = 'order_id';

        $this->resourceArchive->expects($this->once())
            ->method('getOrderIdsForArchive')
            ->with($this->equalTo($ids), $this->equalTo(false))
            ->will($this->returnValue($ids));

        $this->resourceArchive->expects($this->at(2))
            ->method('moveToArchive')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::ORDER), $entity, $this->equalTo($ids))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(3))
            ->method('moveToArchive')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::INVOICE), $order, $this->equalTo($ids))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(4))
            ->method('moveToArchive')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::SHIPMENT), $order, $this->equalTo($ids))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(5))
            ->method('moveToArchive')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::CREDITMEMO), $order, $this->equalTo($ids))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(6))
            ->method('removeFromGrid')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::ORDER), $entity, $this->equalTo($ids))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(7))
            ->method('removeFromGrid')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::INVOICE), $order, $this->equalTo($ids))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(8))
            ->method('removeFromGrid')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::SHIPMENT), $order, $this->equalTo($ids))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(9))
            ->method('removeFromGrid')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::CREDITMEMO), $order, $this->equalTo($ids))
            ->will($this->returnSelf());

        $this->resourceArchive->expects($this->at(10))
            ->method('commit')
            ->will($this->returnSelf());

        $event = 'magento_salesarchive_archive_archive_orders';
        $this->eventManager->expects($this->once())
            ->method('dispatch')
            ->with($event, $this->equalTo(['order_ids' => $ids]));

        $result = $this->archive->archiveOrdersById($ids);
        $this->assertEquals($ids, $result);
    }

    /**
     * @expectedException \Exception
     */
    public function testArchiveOrdersByIdException()
    {
        $ids = [100021, 100023, 100054];
        $entity = 'entity_id';

        $this->resourceArchive->expects($this->once())
            ->method('getOrderIdsForArchive')
            ->with($this->equalTo($ids), $this->equalTo(false))
            ->will($this->returnValue($ids));
        $this->resourceArchive->expects($this->at(1))
            ->method('beginTransaction')
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(2))
            ->method('moveToArchive')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::ORDER), $entity, $this->equalTo($ids))
            ->will($this->throwException(new \Exception()));
        $this->resourceArchive->expects($this->at(3))
            ->method('rollback')
            ->will($this->returnSelf());

        $result = $this->archive->archiveOrdersById($ids);
        $this->assertInstanceOf('Exception', $result);
    }

    public function testRemoveOrdersFromArchive()
    {
        $this->resourceArchive->expects($this->once())
            ->method('beginTransaction')
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(1))
            ->method('removeFromArchive')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::ORDER))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(2))
            ->method('removeFromArchive')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::INVOICE))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(3))
            ->method('removeFromArchive')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::SHIPMENT))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(4))
            ->method('removeFromArchive')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::CREDITMEMO))
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(5))
            ->method('commit')
            ->will($this->returnSelf());

        $result = $this->archive->removeOrdersFromArchive();
        $this->assertInstanceOf($this->archiveClassName, $result);
    }

    /**
     * @expectedException \Exception
     */
    public function testRemoveOrdersFromArchiveException()
    {
        $this->resourceArchive->expects($this->once())
            ->method('beginTransaction')
            ->will($this->returnSelf());
        $this->resourceArchive->expects($this->at(1))
            ->method('removeFromArchive')
            ->with($this->equalTo(\Magento\SalesArchive\Model\ArchivalList::ORDER))
            ->will($this->throwException(new \Exception()));
        $this->resourceArchive->expects($this->at(2))
            ->method('rollback')
            ->will($this->returnSelf());
        $result = $this->archive->removeOrdersFromArchive();
        $this->assertInstanceOf('Exception', $result);
    }

    public function testRemoveOrdersFromArchiveById()
    {
        $ids = [100021, 100023, 100054];
        $this->resourceArchive->expects($this->once())
            ->method('removeOrdersFromArchiveById')
            ->with($this->equalTo($ids))
            ->will($this->returnValue($ids));

        $result = $this->archive->removeOrdersFromArchiveById($ids);
        $this->assertEquals($ids, $result);
    }
}
