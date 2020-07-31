<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Test\Unit\Model;

class RoleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdminGws\Model\Role
     */
    private $role;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManagerHelper;

    protected function setUp()
    {
        $this->objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storeManagerMock->expects($this->any())
            ->method('getWebsites')
            ->will($this->returnValue([1 => 'website']));
        $storeManagerMock->expects($this->any())
            ->method('getStores')
            ->will($this->returnValue([1 => 'store']));
        $storeManagerMock->expects($this->any())
            ->method('getGroups')
            ->will($this->returnValue([1 => 'group']));
        $this->role = $this->objectManagerHelper->getObject(
            \Magento\AdminGws\Model\Role::class,
            [
                'storeManager' => $storeManagerMock
            ]
        );
    }

    /**
     * @param $gwsRelevantWebsites
     * @param $gwsStores
     * @param $gwsStoreGroups
     * @param $gwsWebsites
     * @dataProvider adminRoleDataProvider
     */
    public function testSetAdminRole(
        $gwsRelevantWebsites,
        $gwsStores,
        $gwsStoreGroups,
        $gwsWebsites
    ) {
        $adminRole = $this->objectManagerHelper->getObject(
            \Magento\Authorization\Model\Role::class,
            [
                'data' => [
                    'gws_relevant_websites' => $gwsRelevantWebsites,
                    'gws_stores' => $gwsStores,
                    'gws_store_groups' => $gwsStoreGroups,
                    'gws_websites' => $gwsWebsites,
                ]
            ]
        );
        $this->role->setAdminRole($adminRole);
        $this->assertTrue(is_array($this->role->getStoreGroupIds()));
        $this->assertTrue(is_array($this->role->getWebsiteIds()));
        $this->assertTrue(is_array($this->role->getStoreIds()));
        $this->assertTrue(is_array($this->role->getRelevantWebsiteIds()));
    }

    public function adminRoleDataProvider()
    {
        return [
            [null, null, null, null],
            [
                [1, 2, 3],
                [1, 2, 3],
                [1, 2, 3],
                [1, 2, 3],
            ],
        ];
    }
}
