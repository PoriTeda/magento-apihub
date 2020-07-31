<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Model\Store;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Customer\Model\Customer;

/**
 * Unit test for \Magento\AdminGws\Model\Models.
 */
class ModelsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdminGws\Model\Models
     */
    private $model;

    /**
     * @var \Magento\AdminGws\Model\Role|MockObject
     */
    private $role;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->role = $this->getMockBuilder(\Magento\AdminGws\Model\Role::class)
            ->disableOriginalConstructor()
            ->setMethods(['hasStoreAccess', 'hasExclusiveStoreAccess', 'getStoreIds', 'getDisallowedStoreIds'])
            ->getMock();
        $this->model = $this->objectManagerHelper->getObject(
            \Magento\AdminGws\Model\Models::class,
            [
                'role' => $this->role,
            ]
        );
    }

    /**
     * @return void
     */
    public function testCmsPageSaveBefore()
    {
        $pageId = 1;
        $storeIds = [Store::DEFAULT_STORE_ID];

        /** @var \Magento\Cms\Model\ResourceModel\Page|MockObject $cmsPageResource */
        $cmsPageResource = $this->getMockBuilder(\Magento\Cms\Model\ResourceModel\Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['lookupStoreIds'])
            ->getMock();
        $cmsPageResource->expects($this->once())->method('lookupStoreIds')
            ->with($pageId)
            ->willReturn($storeIds);

        $this->role->expects($this->once())->method('hasStoreAccess')
            ->with($storeIds)
            ->willReturn(true);
        $this->role->expects($this->once())->method('hasExclusiveStoreAccess')
            ->with($storeIds)
            ->willReturn(true);
        $this->role->expects($this->atLeastOnce())->method('getStoreIds')->willReturn($storeIds);
        $this->role->expects($this->once())->method('getDisallowedStoreIds')->willReturn([]);

        /** @var \Magento\Cms\Model\Page|MockObject $cmsPage */
        $cmsPage = $this->getMockBuilder(\Magento\Cms\Model\Page::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResource', 'getId', 'getStoreId', 'setStoreId'])
            ->getMock();

        $cmsPage->expects($this->once())->method('getResource')->willReturn($cmsPageResource);
        $cmsPage->expects($this->exactly(2))->method('getId')->willReturn($pageId);
        $cmsPage->expects($this->once())->method('getStoreId')->willReturn($storeIds);
        $cmsPage->expects($this->once())->method('setStoreId')->with($storeIds);

        $this->model->cmsPageSaveBefore($cmsPage);
    }

    /**
     * Test for method cmsBlockSaveBefore.
     *
     * @return void
     */
    public function testCmsBlockSaveBefore()
    {
        $blockId = 1;
        $storeIds = [Store::DEFAULT_STORE_ID];

        /** @var \Magento\Cms\Model\ResourceModel\Block|MockObject $cmsBlockResource */
        $cmsBlockResource = $this->getMockBuilder(\Magento\Cms\Model\ResourceModel\Block::class)
            ->disableOriginalConstructor()
            ->setMethods(['lookupStoreIds'])
            ->getMock();
        $cmsBlockResource->expects($this->once())->method('lookupStoreIds')
            ->with($blockId)
            ->willReturn($storeIds);

        $this->role->expects($this->once())->method('hasStoreAccess')
            ->with($storeIds)
            ->willReturn(true);
        $this->role->expects($this->once())->method('hasExclusiveStoreAccess')
            ->with($storeIds)
            ->willReturn(true);
        $this->role->expects($this->atLeastOnce())->method('getStoreIds')->willReturn($storeIds);
        $this->role->expects($this->once())->method('getDisallowedStoreIds')->willReturn([]);

        /** @var \Magento\Cms\Model\Block|MockObject $cmsBlock */
        $cmsBlock = $this->getMockBuilder(\Magento\Cms\Model\Block::class)
            ->disableOriginalConstructor()
            ->setMethods(['getResource', 'getId', 'getStoreId', 'setStoreId'])
            ->getMock();

        $cmsBlock->expects($this->once())->method('getResource')->willReturn($cmsBlockResource);
        $cmsBlock->expects($this->exactly(2))->method('getId')->willReturn($blockId);
        $cmsBlock->expects($this->once())->method('getStoreId')->willReturn($storeIds);
        $cmsBlock->expects($this->once())->method('setStoreId')->with($storeIds);

        $this->model->cmsBlockSaveBefore($cmsBlock);
    }

    /**
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage More permissions are needed to view this item.
     * @return void
     */
    public function testCustomerLoadAfter(): void
    {
        $store1 = 1;
        $store2 = 2;
        /** @var \PHPUnit_Framework_MockObject_MockObject|Customer $customerMock */
        $customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId'])
            ->getMock();
        $customerMock->expects($this->exactly(2))
            ->method('getStoreId')
            ->willReturnOnConsecutiveCalls($store1, $store2);
        $this->role->expects($this->exactly(2))
            ->method('hasStoreAccess')
            ->withConsecutive([$store1], [$store2])
            ->willReturnOnConsecutiveCalls(true, false);

        //Website #1 is allowed for the role.
        $this->model->customerLoadAfter($customerMock);
        //Website #2 is NOT allowed.
        $this->model->customerLoadAfter($customerMock);
    }
}
