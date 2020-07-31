<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Observer;

use Magento\CatalogPermissions\Observer\ApplyCategoryInactiveIdsObserver;
use Magento\Framework\DataObject;

/**
 * Test for \Magento\CatalogPermissions\Observer\ApplyCategoryInactiveIdsObserver
 */
class ApplyCategoryInactiveIdsObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogPermissions\Observer\ApplyCategoryInactiveIdsObserver
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
     * @var \Magento\Store\Model\StoreManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventObserverMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->storeManager = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->permissionsConfig = $this->createMock(\Magento\CatalogPermissions\App\ConfigInterface::class);
        $this->permissionIndex = $this->createMock(\Magento\CatalogPermissions\Model\Permission\Index::class);

        $this->eventObserverMock = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer = new ApplyCategoryInactiveIdsObserver(
            $this->permissionsConfig,
            $this->storeManager,
            $this->createMock(\Magento\Customer\Model\Session::class),
            $this->permissionIndex
        );
    }

    /**
     * @return void
     */
    public function testApplyCategoryInactiveIds()
    {
        $this->storeManager
            ->expects($this->any())
            ->method('getStore')
            ->willReturn(new DataObject(['website_id' => 123]));

        $this->permissionsConfig
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);
        $this->permissionIndex
            ->expects($this->any())
            ->method('getRestrictedCategoryIds')
            ->with($this->anything(), 123)
            ->willReturn([3, 2, 1]);

        $treeMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category\Tree::class)
            ->disableOriginalConstructor()
            ->getMock();
        $treeMock
            ->expects($this->once())
            ->method('addInactiveCategoryIds')
            ->with([3, 2, 1]);

        $this->eventObserverMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn(new DataObject(['tree' => $treeMock]));

        $this->observer->execute($this->eventObserverMock);
    }
}
