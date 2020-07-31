<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Observer;

use Magento\CatalogPermissions\Observer\ApplyIsSalableToProductObserver;
use Magento\Framework\DataObject;

/**
 * Test for \Magento\CatalogPermissions\Observer\ApplyIsSalableToProductObserver
 */
class ApplyIsSalableToProductObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogPermissions\Observer\ApplyIsSalableToProductObserver
     */
    protected $observer;

    /**
     * @var \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventObserverMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->eventObserverMock = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer = new ApplyIsSalableToProductObserver();
    }

    /**
     * @return void
     */
    public function testApplyIsSalableToProduct()
    {
        $salableMock = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->setMethods(['setIsSalable'])
            ->disableOriginalConstructor()
            ->getMock();

        $salableMock
            ->expects($this->once())
            ->method('setIsSalable')
            ->with(false);

        $this->eventObserverMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn(
                new DataObject(
                    [
                        'salable' => $salableMock,
                        'product' => new DataObject(['disable_add_to_cart' => true])
                    ]
                )
            );

        $this->observer->execute($this->eventObserverMock);
    }
}
