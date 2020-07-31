<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Test\Unit\Observer;

class UpdateRoleStoresTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdminGws\Observer\UpdateRoleStores
     */
    protected $_updateRoleStoresObserver;

    /**
     * @var \Magento\AdminGws\Model\Role
     */
    protected $_role;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $_store;

    /**
     * @var \Magento\Framework\Event\Observer
     */
    protected $_observer;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->_store = new \Magento\Framework\DataObject();

        $this->_role = $this->getMockBuilder(
            \Magento\AdminGws\Model\Role::class
        )->setMethods(
            ['getStoreIds', 'setStoreIds']
        )->disableOriginalConstructor()->getMock();

        $this->_observer = $this->getMockBuilder(
            \Magento\Framework\Event\Observer::class
        )->setMethods(
            ['getStore']
        )->disableOriginalConstructor()->getMock();

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->_updateRoleStoresObserver = $objectManagerHelper->getObject(
            \Magento\AdminGws\Observer\UpdateRoleStores::class,
            [
                'role' => $this->_role,
            ]
        );
    }

    public function testUpdateRoleStores()
    {
        $this->_store->setData('store_id', 1000);
        $this->_role->expects($this->any())->method('getStoreIds')->will($this->returnValue([1, 2, 3, 4, 5]));
        $this->_observer->expects($this->any())->method('getStore')->will($this->returnValue($this->_store));
        $this->_role->expects($this->once())->method('setStoreIds')->with($this->contains(1000));
        $this->_updateRoleStoresObserver->execute($this->_observer);
    }
}
