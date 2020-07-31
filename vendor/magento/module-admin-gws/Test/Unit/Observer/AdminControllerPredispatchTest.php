<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdminGws\Test\Unit\Observer;

use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Unit test for \Magento\AdminGws\Observer\AdminControllerPredispatch
 */
class AdminControllerPredispatchTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdminGws\Observer\AdminControllerPredispatch
     */
    private $adminControllerPredispatchObserver;

    /**
     * @var \Magento\Backend\Model\Auth\Session|MockObject
     */
    private $backendAuthSession;

    /**
     * @var \Magento\User\Model\User|MockObject
     */
    private $user;

    /**
     * @var \Magento\Authorization\Model\Role|MockObject
     */
    private $role;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->user = $this->createMock(\Magento\User\Model\User::class);
        $this->role = $this->createMock(\Magento\AdminGws\Model\Role::class);
        $this->backendAuthSession = $this->getMockBuilder(\Magento\Backend\Model\Auth\Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn', 'getUser'])
            ->getMock();
        $this->backendAuthSession->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->backendAuthSession->expects($this->any())->method('getUser')->willReturn($this->user);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->adminControllerPredispatchObserver = $objectManagerHelper->getObject(
            \Magento\AdminGws\Observer\AdminControllerPredispatch::class,
            [
                'role' => $this->role,
                'backendAuthSession' => $this->backendAuthSession,
            ]
        );
    }

    /**
     * @return void
     */
    public function testAdminControllerPredispatchGwsDataPresent()
    {
        $role = $this->getAdminRoleMock();
        $role->expects($this->once())->method('getGwsDataIsset')->willReturn(true);
        $role->expects($this->never())->method('load');

        $this->user->expects($this->any())->method('getRole')->willReturn($role);
        $this->role->expects($this->any())->method('getIsAll')->willReturn(true);
        $this->role->expects($this->once())->method('setAdminRole')->with($role);

        /** @var \Magento\Framework\Event\Observer|MockObject $observer */
        $observer = $this->createMock(\Magento\Framework\Event\Observer::class);

        $this->adminControllerPredispatchObserver->execute($observer);
    }

    /**
     * @return void
     */
    public function testAdminControllerPredispatchGwsDataMissing()
    {
        $role = $this->getAdminRoleMock();
        $role->expects($this->once())->method('getGwsDataIsset')->willReturn(false);
        $role->expects($this->once())->method('load');

        $this->user->expects($this->any())->method('getRole')->willReturn($role);
        $this->role->expects($this->any())->method('getIsAll')->willReturn(true);
        $this->role->expects($this->once())->method('setAdminRole')->with($role);

        /** @var \Magento\Framework\Event\Observer|MockObject $observer */
        $observer = $this->createMock(\Magento\Framework\Event\Observer::class);

        $this->adminControllerPredispatchObserver->execute($observer);
    }

    /**
     * @return \Magento\Authorization\Model\Role|MockObject
     */
    private function getAdminRoleMock()
    {
        return $this->getMockBuilder(\Magento\Authorization\Model\Role::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGwsDataIsset', 'load'])
            ->getMock();
    }
}
