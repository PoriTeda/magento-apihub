<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Observer;

use Magento\CatalogPermissions\Observer\ApplyProductPermissionOnCollectionObserver;
use Magento\Framework\DataObject;

/**
 * Test for \Magento\CatalogPermissions\Observer\ApplyProductPermissionOnCollectionObserver
 */
class ApplyProductPermissionOnCollectionObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogPermissions\Observer\ApplyProductPermissionOnCollectionObserver
     */
    protected $observer;

    /**
     * @var \Magento\CatalogPermissions\App\ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $permissionsConfig;

    /**
     * @var \Magento\CatalogPermissions\Model\Permission\Index|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $permissionIndex;

    /**
     * @var \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventObserverMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->permissionsConfig = $this->createMock(\Magento\CatalogPermissions\App\ConfigInterface::class);
        $this->permissionIndex = $this->createMock(\Magento\CatalogPermissions\Model\Permission\Index::class);

        $this->eventObserverMock = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer = new ApplyProductPermissionOnCollectionObserver(
            $this->permissionsConfig,
            $this->createMock(\Magento\Customer\Model\Session::class),
            $this->permissionIndex
        );
    }

    /**
     * @return void
     */
    public function testApplyProductPermissionOnCollection()
    {
        $this->permissionsConfig
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $this->eventObserverMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn(new DataObject(['collection' => [1, 2, 3]]));

        $this->permissionIndex
            ->expects($this->once())
            ->method('addIndexToProductCollection')
            ->with([1, 2, 3], $this->anything());

        $this->observer->execute($this->eventObserverMock);
    }
}
