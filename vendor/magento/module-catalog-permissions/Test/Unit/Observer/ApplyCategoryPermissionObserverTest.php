<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Observer;

use Magento\CatalogPermissions\Observer\ApplyCategoryPermissionObserver;
use Magento\Framework\DataObject;

/**
 * Test for \Magento\CatalogPermissions\Observer\ApplyCategoryPermissionObserver
 */
class ApplyCategoryPermissionObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogPermissions\Observer\ApplyCategoryPermissionObserver
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

        $this->observer = new ApplyCategoryPermissionObserver(
            $this->permissionsConfig,
            $this->storeManager,
            $this->createMock(\Magento\Customer\Model\Session::class),
            $this->permissionIndex,
            $this->createMock(\Magento\CatalogPermissions\Helper\Data::class),
            $this->createMock(\Magento\CatalogPermissions\Observer\ApplyPermissionsOnCategory::class)
        );
    }

    /**
     * @return void
     */
    public function testApplyCategoryPermission()
    {
        $this->permissionsConfig
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $this->storeManager
            ->expects($this->any())
            ->method('getStore')
            ->willReturn(new DataObject(['website_id' => 123]));

        $categoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->setMethods(['getIsHidden', 'getId', 'setPermissions'])
            ->disableOriginalConstructor()
            ->getMock();
        $categoryMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(33);
        $categoryMock
            ->expects($this->any())
            ->method('getIsHidden')
            ->willReturn(false);
        $categoryMock
            ->expects($this->once())
            ->method('setPermissions')
            ->with(1);

        $this->permissionIndex
            ->expects($this->any())
            ->method('getIndexForCategory')
            ->willReturn([33 => 1]);

        $responseMock = $this->getMockBuilder(\Magento\Framework\App\ResponseInterface::class)
            ->setMethods(['setRedirect', 'sendResponse'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventObserverMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn(
                new DataObject(
                    [
                        'category' => $categoryMock,
                        'controller_action' => new DataObject(['response' => $responseMock])
                    ]
                )
            );

        $this->observer->execute($this->eventObserverMock);
    }

    /**
     * @return void
     * @expectedException \Magento\Framework\Exception\LocalizedException
     */
    public function testApplyCategoryPermissionException()
    {
        $this->permissionsConfig
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);

        $this->storeManager
            ->expects($this->any())
            ->method('getStore')
            ->willReturn(new DataObject(['website_id' => 123]));

        $categoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->setMethods(['getIsHidden', 'getId', 'setPermissions'])
            ->disableOriginalConstructor()
            ->getMock();
        $categoryMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(33);
        $categoryMock
            ->expects($this->any())
            ->method('getIsHidden')
            ->willReturn(true);
        $categoryMock
            ->expects($this->once())
            ->method('setPermissions')
            ->with(1);

        $this->permissionIndex
            ->expects($this->any())
            ->method('getIndexForCategory')
            ->willReturn([33 => 1]);

        $responseMock = $this->getMockBuilder(\Magento\Framework\App\ResponseInterface::class)
            ->setMethods(['setRedirect', 'sendResponse'])
            ->disableOriginalConstructor()
            ->getMock();
        $responseMock
            ->expects($this->once())
            ->method('setRedirect');

        $this->eventObserverMock
            ->expects($this->any())
            ->method('getEvent')
            ->willReturn(
                new DataObject(
                    [
                        'category' => $categoryMock,
                        'controller_action' => new DataObject(['response' => $responseMock])
                    ]
                )
            );

        $this->observer->execute($this->eventObserverMock);
    }
}
