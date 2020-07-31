<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Test\Unit\Observer;

class RefreshRolePermissionsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdminGws\Observer\RefreshRolePermissions
     */
    protected $_refreshRolePermissionsObserver;

    /**
     * @var \Magento\Backend\Model\Auth\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_backendAuthSession;

    /**
     * @var \Magento\AdminGws\Observer\RefreshRolePermissions|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_rolePermissionAssigner;

    /**
     * @var \Magento\Framework\Event\Observer
     */
    protected $_observer;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_store;

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->_backendAuthSession = $this->createPartialMock(
            \Magento\Backend\Model\Auth\Session::class,
            ['getUser']
        );

        $this->_store = new \Magento\Framework\DataObject();

        $this->_observer = $this->getMockBuilder(
            \Magento\Framework\Event\Observer::class
        )->setMethods(
            ['getStore']
        )->disableOriginalConstructor()->getMock();
        $this->_observer->expects($this->any())->method('getStore')->will($this->returnValue($this->_store));

        $this->_rolePermissionAssigner = $this->getMockBuilder(
            \Magento\AdminGws\Observer\RolePermissionAssigner::class
        )->setMethods(
            []
        )->disableOriginalConstructor()->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->_refreshRolePermissionsObserver = $objectManagerHelper->getObject(
            \Magento\AdminGws\Observer\RefreshRolePermissions::class,
            [
                'rolePermissionAssigner' => $this->_rolePermissionAssigner,
                'backendAuthSession' => $this->_backendAuthSession
            ]
        );
    }

    public function testRefreshRolePermissions()
    {
        /** @var \Magento\Authorization\Model\Role|\PHPUnit_Framework_MockObject_MockObject $role */
        $role = $this->createMock(\Magento\Authorization\Model\Role::class);

        $user = $this->createMock(\Magento\User\Model\User::class);
        $user->expects($this->once())->method('getRole')->will($this->returnValue($role));

        $this->_backendAuthSession->expects($this->once())->method('getUser')->will($this->returnValue($user));

        $this->_refreshRolePermissionsObserver->execute($this->_observer);
    }

    public function testRefreshRolePermissionsInvalidUser()
    {
        $user = $this->createPartialMock(\stdClass::class, ['getRole']);
        $user->expects($this->never())->method('getRole');

        $this->_backendAuthSession->expects($this->once())->method('getUser')->will($this->returnValue($user));

        $this->_refreshRolePermissionsObserver->execute($this->_observer);
    }
}
