<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Model\Report\Group\Stores;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

abstract class AbstractTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Model\Report\Group\Stores\AbstractStoresSection
     */
    protected $section;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Store\Model\StoreManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Collection\Factory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryCollectionFactoryMock;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryCollectionMock;

    /**
     * @param string $sectionClass
     */
    protected function prepareObjects($sectionClass)
    {
        $this->storeManagerMock = $this->getMockBuilder(\Magento\Store\Model\StoreManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryCollectionFactoryMock = $this
            ->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category\Collection\Factory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryCollectionMock = $this->getMockBuilder(
            \Magento\Catalog\Model\ResourceModel\Category\Collection::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryCollectionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->categoryCollectionMock);
        $this->categoryCollectionMock->expects($this->any())
            ->method('addNameToResult')
            ->willReturnSelf();
        $this->categoryCollectionMock->expects($this->any())
            ->method('addRootLevelFilter')
            ->willReturnSelf();
        $this->categoryCollectionMock->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->section = $this->objectManagerHelper->getObject(
            $sectionClass,
            [
                'storeManager' => $this->storeManagerMock,
                'categoryCollectionFactory' => $this->categoryCollectionFactoryMock
            ]
        );
    }

    /**
     * Get website mock object
     *
     * @param array $data
     * @return \Magento\Store\Model\Website|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWebsiteMock(array $data)
    {
        $data = array_merge(
            array_fill_keys(
                [
                    'id', 'name', 'code', 'is_default', 'default_group_id',
                    'default_group', 'default_store', 'groups'
                ],
                null
            ),
            $data
        );
        $websiteMock = $this->getMockBuilder(\Magento\Store\Model\Website::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'getId', 'getName', 'getCode', 'getIsDefault', 'getDefaultGroupId',
                    'getDefaultGroup', 'getDefaultStore', 'getGroups'
                ]
            )
            ->getMock();

        $websiteMock->expects($this->any())
            ->method('getId')
            ->willReturn($data['id']);
        $websiteMock->expects($this->any())
            ->method('getName')
            ->willReturn($data['name']);
        $websiteMock->expects($this->any())
            ->method('getCode')
            ->willReturn($data['code']);
        $websiteMock->expects($this->any())
            ->method('getIsDefault')
            ->willReturn($data['is_default']);
        $websiteMock->expects($this->any())
            ->method('getDefaultGroupId')
            ->willReturn($data['default_group_id']);
        $websiteMock->expects($this->any())
            ->method('getDefaultGroup')
            ->willReturn($data['default_group']);
        $websiteMock->expects($this->any())
            ->method('getDefaultStore')
            ->willReturn($data['default_store']);
        $websiteMock->expects($this->any())
            ->method('getGroups')
            ->willReturn($data['groups']);

        return $websiteMock;
    }

    /**
     * Get store mock object
     *
     * @param array $data
     * @return \Magento\Store\Model\Group|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getStoreMock(array $data)
    {
        $data = array_merge(
            array_fill_keys(
                ['id', 'name', 'root_category_id', 'default_store_id', 'default_store', 'stores'],
                null
            ),
            $data
        );
        $storeMock = $this->getMockBuilder(\Magento\Store\Model\Group::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storeMock->expects($this->any())
            ->method('getId')
            ->willReturn($data['id']);
        $storeMock->expects($this->any())
            ->method('getName')
            ->willReturn($data['name']);
        $storeMock->expects($this->any())
            ->method('getRootCategoryId')
            ->willReturn($data['root_category_id']);
        $storeMock->expects($this->any())
            ->method('getDefaultStoreId')
            ->willReturn($data['default_store_id']);
        $storeMock->expects($this->any())
            ->method('getDefaultStore')
            ->willReturn($data['default_store']);
        $storeMock->expects($this->any())
            ->method('getStores')
            ->willReturn($data['stores']);

        return $storeMock;
    }

    /**
     * Get store view mock object
     *
     * @param array $data
     * @return \Magento\Store\Model\Store|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getStoreViewMock(array $data)
    {
        $data = array_merge(
            array_fill_keys(['id', 'name', 'code', 'is_active', 'store_id', 'group'], null),
            $data
        );
        $storeViewMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getName', 'getCode', 'getIsActive', 'getStoreId', 'getGroup'])
            ->getMock();

        $storeViewMock->expects($this->any())
            ->method('getId')
            ->willReturn($data['id']);
        $storeViewMock->expects($this->any())
            ->method('getName')
            ->willReturn($data['name']);
        $storeViewMock->expects($this->any())
            ->method('getCode')
            ->willReturn($data['code']);
        $storeViewMock->expects($this->any())
            ->method('getIsActive')
            ->willReturn($data['is_active']);
        $storeViewMock->expects($this->any())
            ->method('getStoreId')
            ->willReturn($data['store_id']);
        $storeViewMock->expects($this->any())
            ->method('getGroup')
            ->willReturn($data['group']);

        return $storeViewMock;
    }

    /**
     * Get category mock
     *
     * @param array $data
     * @return \Magento\Catalog\Model\Category|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCategoryMock(array $data)
    {
        $data = array_merge(
            array_fill_keys(['name'], null),
            $data
        );
        $categoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $categoryMock->expects($this->any())
            ->method('getName')
            ->willReturn($data['name']);

        return $categoryMock;
    }
}
