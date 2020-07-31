<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Model\Indexer\Plugin;

class GroupRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\Indexer\IndexerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexerMock;

    /**
     * @var \Magento\CatalogPermissions\App\ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $appConfigMock;

    /**
     * @var \Magento\CatalogPermissions\Model\Indexer\Plugin\GroupRepository
     */
    protected $groupRepositoryPlugin;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexerRegistryMock;

    /**
     * @var \Magento\CatalogPermissions\Model\Indexer\UpdateIndexInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $updateIndexMock;

    protected function setUp()
    {
        $this->indexerMock = $this->createPartialMock(
            \Magento\Indexer\Model\Indexer::class,
            ['getId', 'load', 'invalidate']
        );

        $this->appConfigMock = $this->createPartialMock(
            \Magento\CatalogPermissions\App\Backend\Config::class,
            ['isEnabled']
        );

        $this->indexerRegistryMock = $this->createPartialMock(
            \Magento\Framework\Indexer\IndexerRegistry::class,
            ['get']
        );

        $this->updateIndexMock = $this->createMock(
            \Magento\CatalogPermissions\Model\Indexer\UpdateIndexInterface::class
        );

        $this->groupRepositoryPlugin = new \Magento\CatalogPermissions\Model\Indexer\Plugin\GroupRepository(
            $this->indexerRegistryMock,
            $this->appConfigMock,
            $this->updateIndexMock
        );
    }

    public function testAfterDeleteGroupIndexerOff()
    {
        $customerGroupService = $this->createMock(\Magento\Customer\Model\ResourceModel\GroupRepository::class);
        $this->appConfigMock->expects($this->once())->method('isEnabled')->will($this->returnValue(false));
        $this->indexerRegistryMock->expects($this->never())->method('get');
        $this->groupRepositoryPlugin->afterDelete($customerGroupService);
    }

    public function testAfterDeleteIndexerOn()
    {
        $customerGroupService = $this->createMock(\Magento\Customer\Model\ResourceModel\GroupRepository::class);
        $this->appConfigMock->expects($this->once())->method('isEnabled')->will($this->returnValue(true));
        $this->indexerRegistryMock->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [$this->equalTo(\Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)],
                [$this->equalTo(\Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID)]
            )
            ->willReturn($this->indexerMock);
        $this->indexerMock->expects($this->exactly(2))->method('invalidate');
        $this->groupRepositoryPlugin->afterDelete($customerGroupService);
    }

    public function testAfterSaveNoNeedInvalidating()
    {
        $customerGroupService = $this->createMock(\Magento\Customer\Model\ResourceModel\GroupRepository::class);

        $customerGroupMock = $this->createPartialMock(\Magento\Customer\Model\Data\Group::class, ['getId']);
        $customerGroupMock->expects($this->once())->method('getId')->will($this->returnValue(10));
        $this->appConfigMock->expects($this->never())->method('isEnabled')->will($this->returnValue(true));

        $proceedMock = function ($customerGroupMock) {
            return $customerGroupMock;
        };

        $this->groupRepositoryPlugin->aroundSave($customerGroupService, $proceedMock, $customerGroupMock);
    }

    public function testAfterSaveInvalidating()
    {
        $customerGroupService = $this->createMock(\Magento\Customer\Model\ResourceModel\GroupRepository::class);

        $customerGroupMock = $this->createPartialMock(\Magento\Customer\Model\Data\Group::class, ['getId']);
        $customerGroupMock->expects($this->once())->method('getId')->will($this->returnValue(0));
        $this->appConfigMock->expects($this->once())->method('isEnabled')->will($this->returnValue(true));
        $this->updateIndexMock->expects($this->any())->method('update');

        $proceedMock = function ($customerGroupMock) {
            return $customerGroupMock;
        };

        $this->groupRepositoryPlugin->aroundSave($customerGroupService, $proceedMock, $customerGroupMock);
    }

    protected function prepareIndexer()
    {
        $this->indexerMock->expects($this->once())->method('getId')->will($this->returnValue(0));
        $this->indexerMock->expects(
            $this->once()
        )->method(
            'load'
        )->with(
            \Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID
        )->will(
            $this->returnSelf()
        );
    }
}
