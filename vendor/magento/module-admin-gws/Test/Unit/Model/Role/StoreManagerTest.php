<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Model\Role;

use Magento\AdminGws\Model\Role;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class StoreManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Role|MockObject
     */
    private $roleMock;

    /**
     * @var Role\StoreManager
     */
    private $roleStoreManager;

    /**
     * @var StoreManagerInterface|MockObject
     */
    private $storeManagerMock;

    /**
     * @var StoreInterface|MockObject
     */
    private $storeMockA;

    /**
     * @var StoreInterface|MockObject
     */
    private $storeMockB;

    /**
     * @var WebsiteInterface|MockObject
     */
    private $websiteMockA;

    /**
     * @var WebsiteInterface|MockObject
     */
    private $websiteMockB;

    /**
     * @var GroupInterface|MockObject
     */
    private $groupMockA;

    /**
     * @var GroupInterface|MockObject
     */
    private $groupMockB;

    /**
     * Class dependencies initialization
     *
     * @return void
     */
    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMockForAbstractClass();
        $this->storeMockA = $this->getMockBuilder(StoreInterface::class)
            ->getMockForAbstractClass();
        $this->storeMockA->method('getId')
            ->willReturn(1);
        $this->storeMockB = $this->getMockBuilder(StoreInterface::class)
            ->getMockForAbstractClass();
        $this->storeMockB->method('getId')
            ->willReturn(2);
        $this->storeManagerMock->method('getStores')
            ->willReturn([$this->storeMockA, $this->storeMockB]);

        $this->websiteMockA = $this->getMockBuilder(WebsiteInterface::class)
            ->getMockForAbstractClass();
        $this->websiteMockA->method('getId')
            ->willReturn(1);
        $this->websiteMockB = $this->getMockBuilder(WebsiteInterface::class)
            ->getMockForAbstractClass();
        $this->websiteMockB->method('getId')
            ->willReturn(2);
        $this->storeManagerMock->method('getWebsites')
            ->willReturn([$this->websiteMockA, $this->websiteMockB]);
        $this->groupMockA = $this->getMockBuilder(WebsiteInterface::class)
            ->getMockForAbstractClass();
        $this->groupMockA->method('getId')
            ->willReturn(1);
        $this->groupMockB = $this->getMockBuilder(WebsiteInterface::class)
            ->getMockForAbstractClass();
        $this->groupMockB->method('getId')
            ->willReturn(2);
        $this->storeManagerMock->method('getGroups')
            ->willReturn([$this->groupMockA, $this->groupMockB]);
        $this->roleMock = $this->getMockBuilder(Role::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->roleStoreManager = $objectManager->getObject(
            Role\StoreManager::class,
            [
                'role' => $this->roleMock,
                'storeManager' => $this->storeManagerMock,
            ]
        );
    }

    /**
     * @param bool $isAll
     * @param int[] $roleStoreIds
     * @param int[] $expectedStores
     * @dataProvider dataProvider
     */
    public function testGetStores($isAll, $roleStoreIds, $expectedStores)
    {
        $this->roleMock->expects($this->once())
            ->method('getIsAll')
            ->willReturn($isAll);
        $this->roleMock->expects($this->exactly($isAll ? 0 : 1))
            ->method('getStoreIds')
            ->willReturn($roleStoreIds);
        $expected = [];
        foreach ($expectedStores as $expectedStore) {
            $expectedStore = 'storeMock' . $expectedStore;
            $expected[] = $this->$expectedStore;
        }
        $this->assertEquals($expected, $this->roleStoreManager->getStores());
    }

    /**
     * @param bool $isAll
     * @param int[] $roleWebsiteIds
     * @param int[] $expectedWebsites
     * @dataProvider dataProvider
     */
    public function testGetWebsites($isAll, $roleWebsiteIds, $expectedWebsites)
    {
        $this->roleMock->expects($this->once())
            ->method('getIsAll')
            ->willReturn($isAll);
        $this->roleMock->expects($this->exactly($isAll ? 0 : 1))
            ->method('getRelevantWebsiteIds')
            ->willReturn($roleWebsiteIds);
        $expected = [];
        foreach ($expectedWebsites as $expectedWebsite) {
            $expectedWebsite = 'websiteMock' . $expectedWebsite;
            $expected[] = $this->$expectedWebsite;
        }
        $this->assertEquals($expected, $this->roleStoreManager->getWebsites());
    }

    /**
     * @param bool $isAll
     * @param int[] $roleGroupIds
     * @param int[] $expectedGroups
     * @dataProvider dataProvider
     */
    public function testGetGroups($isAll, $roleGroupIds, $expectedGroups)
    {
        $this->roleMock->expects($this->once())
            ->method('getIsAll')
            ->willReturn($isAll);
        $this->roleMock->expects($this->exactly($isAll ? 0 : 1))
            ->method('getStoreGroupIds')
            ->willReturn($roleGroupIds);
        $expected = [];
        foreach ($expectedGroups as $expectedGroup) {
            $expectedGroup = 'groupMock' . $expectedGroup;
            $expected[] = $this->$expectedGroup;
        }
        $this->assertEquals($expected, $this->roleStoreManager->getGroups());
    }

    public function dataProvider() : array
    {
        return [
            'isAll' => [true, [1, 2], ['A', 'B']],
            'A & B' => [false, [1, 2], ['A', 'B']],
            'A' => [false, [1], ['A']],
        ];
    }

    /**
     * @param bool $hasDefaultStoreView
     * @param string[] $roleStoreIds
     * @param bool $roleHasDefaultStoreView
     * @dataProvider getDefaultStoreViewDataProvider
     */
    public function testGetDefaultStoreView($hasDefaultStoreView, $roleStoreIds, $roleHasDefaultStoreView)
    {
        $this->storeManagerMock->expects($this->once())
            ->method('getDefaultStoreView')
            ->willReturn($hasDefaultStoreView ? $this->storeMockA : null);
        $this->roleMock->expects($this->exactly($hasDefaultStoreView ? 1 : 0))
            ->method('getStoreIds')
            ->willReturn($roleStoreIds);
        $expected = $roleHasDefaultStoreView ? $this->storeMockA : null;

        $this->assertEquals($expected, $this->roleStoreManager->getDefaultStoreView());
    }

    public function getDefaultStoreViewDataProvider() : array
    {
        return [
            [true, [2], false],
            [true, [1, 2], true],
            [true, [1], true],
            [true, [], false],
            [false, [1, 2], false],
            [false, [], false],
        ];
    }
}
