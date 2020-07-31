<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Test\Unit\Model\Category;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class EventListTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\CatalogEvent\Model\Category\EventList */
    protected $eventList;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $collectionFactory;

    /** @var \Magento\CatalogEvent\Model\ResourceModel\Event\Collection|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventCollection;

    /** @var \Magento\CatalogEvent\Model\ResourceModel\EventFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventFactory;

    /** @var \Magento\CatalogEvent\Model\ResourceModel\Event|\PHPUnit_Framework_MockObject_MockObject */
    protected $resourceEvent;

    protected function setUp()
    {
        $this->registry = $this->createMock(\Magento\Framework\Registry::class);
        $this->collectionFactory = $this->createPartialMock(
            \Magento\CatalogEvent\Model\ResourceModel\Event\CollectionFactory::class,
            ['create']
        );
        $this->eventFactory = $this->createPartialMock(
            \Magento\CatalogEvent\Model\ResourceModel\EventFactory::class,
            ['create']
        );

        $this->eventCollection = $this->createMock(\Magento\CatalogEvent\Model\ResourceModel\Event\Collection::class);
        $this->collectionFactory->expects($this->any())->method('create')->will(
            $this->returnValue($this->eventCollection)
        );
        $this->resourceEvent = $this->createMock(\Magento\CatalogEvent\Model\ResourceModel\Event::class);
        $this->eventFactory->expects($this->any())->method('create')->will(
            $this->returnValue($this->resourceEvent)
        );

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->eventList = $this->objectManagerHelper->getObject(
            \Magento\CatalogEvent\Model\Category\EventList::class,
            [
                'registry' => $this->registry,
                'eventCollectionFactory' => $this->collectionFactory,
                'eventFactory' => $this->eventFactory
            ]
        );
    }

    public function testGetEventInStoreFromCurrentCategory()
    {
        $categoryId = 1;
        /** @var \Magento\CatalogEvent\Model\Event $event */
        $event = $this->createMock(\Magento\CatalogEvent\Model\Event::class);
        /** @var \Magento\Catalog\Model\Category|\PHPUnit_Framework_MockObject_MockObject $category */
        $category = $this->objectManagerHelper->getObject(
            \Magento\Catalog\Model\Category::class,
            [
                'data' => ['id' => $categoryId, 'event' => $event]
            ]
        );
        $this->registry->expects($this->any())->method('registry')->with('current_category')->will(
            $this->returnValue($category)
        );
        $returnEvent = $this->eventList->getEventInStore($categoryId);
        $this->assertEquals($event, $returnEvent);
    }

    /**
     * Data provider for getting list of categories from store
     *
     * @return array
     */
    public function getEventInStoreDataProvider()
    {
        return [
            [
                [2 => 3, 3 => null, 4 => null],
                2,
                3,
            ],
            [
                [2 => 3, 3 => null, 4 => null],
                4,
                null
            ],
            [
                [2 => 3, 3 => null, 4 => null],
                5,
                false
            ]
        ];
    }

    /**
     * @param array $categoryList
     * @param int $categoryId
     * @param mixed $expectedResult
     *
     * @dataProvider getEventInStoreDataProvider
     */
    public function testGetEventInStore($categoryList, $categoryId, $expectedResult)
    {
        $this->resourceEvent->expects($this->once())->method('getCategoryIdsWithEvent')->will(
            $this->returnValue($categoryList)
        );
        $eventCollectionReturnMap = [];
        foreach ($categoryList as $eventId) {
            if ($eventId) {
                $eventCollectionReturnMap[] = [$eventId, $eventId];
            }
        }
        $this->eventCollection->expects($this->any())->method('getItemById')->will(
            $this->returnValueMap($eventCollectionReturnMap)
        );
        $returnEvent = $this->eventList->getEventInStore($categoryId);
        $this->assertEquals($expectedResult, $returnEvent);
    }

    /**
     * Data provider for category-event association array
     *
     * @return array
     */
    public function getCategoryListDataProvider()
    {
        return [
            [
                [2 => 3, 3 => null, 4 => null],
                1,
            ],
            [
                [4 => 3, 3 => 1, 5 => 4],
                3
            ],
            [
                [],
                0
            ],
            [
                [2 => null, 3 => null, 4 => null, 10 => null],
                0
            ],
        ];
    }

    /**
     * @param array $categoryList
     * @param int $getItemCallNumber
     *
     * @dataProvider getCategoryListDataProvider
     */
    public function testGetEventToCategoriesList($categoryList, $getItemCallNumber)
    {
        $this->resourceEvent->expects($this->once())->method('getCategoryIdsWithEvent')->will(
            $this->returnValue($categoryList)
        );

        $event = new \Magento\Framework\DataObject();
        $this->eventCollection->expects($this->exactly($getItemCallNumber))->method('getItemById')->will(
            $this->returnValue($event)
        );
        $eventsToCategory = $this->eventList->getEventToCategoriesList();
        $this->assertInternalType('array', $eventsToCategory);
        foreach ($categoryList as $key => $value) {
            if ($value !== null) {
                $this->assertInstanceOf(\Magento\Framework\DataObject::class, $eventsToCategory[$key]);
            } else {
                $this->assertNull($eventsToCategory[$key]);
            }
        }
    }

    public function testGetEventCollectionWithIds()
    {
        $this->eventCollection->expects($this->once())->method('addFieldToFilter');
        $collection = $this->eventList->getEventCollection([1, 3]);
        $this->assertInstanceOf(\Magento\CatalogEvent\Model\ResourceModel\Event\Collection::class, $collection);
    }

    public function testGetEventCollectionWithoutIds()
    {
        $this->eventCollection->expects($this->never())->method('addFieldToFilter');
        $collection = $this->eventList->getEventCollection();
        $this->assertInstanceOf(\Magento\CatalogEvent\Model\ResourceModel\Event\Collection::class, $collection);
    }
}
