<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Test\Unit\Observer;

class AddDataAfterRoleLoadTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdminGws\Observer\AddDataAfterRoleLoad
     */
    protected $_addDataAfterRoleLoadObserver;

    /**
     * @var \Magento\AdminGws\Observer\RefreshRolePermissions|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_rolePermissionAssigner;

    /**
     * @return void
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp()
    {
        $this->_rolePermissionAssigner = $this->getMockBuilder(
            \Magento\AdminGws\Observer\RolePermissionAssigner::class
        )->setMethods(
            []
        )->disableOriginalConstructor()->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->_addDataAfterRoleLoadObserver = $objectManagerHelper->getObject(
            \Magento\AdminGws\Observer\AddDataAfterRoleLoad::class,
            [
                $this->_rolePermissionAssigner
            ]
        );
    }

    public function testAddDataAfterRoleLoad()
    {
        /** @var \Magento\Authorization\Model\Role|\PHPUnit_Framework_MockObject_MockObject $role */
        $role = $this->createMock(\Magento\Authorization\Model\Role::class);

        $event = $this->createPartialMock(\Magento\Framework\Event::class, ['getObject']);
        $event->expects($this->once())->method('getObject')->will($this->returnValue($role));
        $observer = $this->createMock(\Magento\Framework\Event\Observer::class);
        $observer->expects($this->once())->method('getEvent')->will($this->returnValue($event));

        $this->_addDataAfterRoleLoadObserver->execute($observer);
    }
}
