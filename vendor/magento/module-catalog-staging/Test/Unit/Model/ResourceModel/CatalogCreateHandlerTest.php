<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Model\ResourceModel;

use Magento\CatalogStaging\Model\ResourceModel\CatalogCreateHandler;
use Magento\Eav\Model\ResourceModel\CreateHandler;
use Magento\Eav\Model\ResourceModel\UpdateHandler;
use Magento\Staging\Model\VersionHistoryInterface;
use Magento\CatalogStaging\Model\ResourceModel\AttributeCopier;
use Magento\Staging\Model\VersionManager;

class CatalogCreateHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var  CatalogCreateHandler */
    private $handler;

    /** @var CreateHandler|\PHPUnit_Framework_MockObject_MockObject */
    private $createHandler;

    /** @var UpdateHandler|\PHPUnit_Framework_MockObject_MockObject */
    private $updateHandler;

    /** @var VersionHistoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $versionHistory;

    /** @var  AttributeCopier|\PHPUnit_Framework_MockObject_MockObject */
    private $attributeCopier;

    protected function setUp()
    {
        $this->createHandler = $this->getMockBuilder(CreateHandler::class)->disableOriginalConstructor()
            ->getMock();
        $this->updateHandler = $this->getMockBuilder(UpdateHandler::class)->disableOriginalConstructor()
            ->getMock();
        $this->versionHistory = $this->getMockBuilder(VersionHistoryInterface::class)->disableOriginalConstructor()
            ->getMock();
        $this->attributeCopier = $this->getMockBuilder(AttributeCopier::class)->disableOriginalConstructor()
            ->getMock();

        $this->handler = new CatalogCreateHandler(
            $this->createHandler,
            $this->updateHandler,
            $this->versionHistory,
            $this->attributeCopier
        );
    }

    /**
     * @param $version
     * @dataProvider versionProvider
     */
    public function testExecuteNew($version)
    {
        $entityData = ['name' => 'Category Name', 'created_in' => VersionManager::MIN_VERSION];
        $this->createHandler->expects($this->once())->method('execute')->willReturn($entityData);
        $this->updateHandler->expects($this->never())->method('execute');
        $this->versionHistory->expects($this->once())->method('getCurrentId')->willReturn($version);
        $this->assertEquals(
            $entityData,
            $this->handler->execute(
                \Magento\Catalog\Api\Data\CategoryInterface::class,
                $entityData
            )
        );
    }

    public function testExecuteUpdate()
    {
        $entityData = ['name' => 'Category Name', 'created_in' => time()];
        $this->createHandler->expects($this->never())->method('execute');
        $this->updateHandler->expects($this->once())->method('execute')->willReturn($entityData);
        $this->versionHistory->expects($this->once())->method('getCurrentId')->willReturn(100);
        $this->assertEquals(
            $entityData,
            $this->handler->execute(
                \Magento\Catalog\Api\Data\CategoryInterface::class,
                $entityData
            )
        );
    }

    public function testExecuteMoveToOtherUpdate()
    {
        $entityData = ['name' => 'Category Name', 'created_in' => 100];
        $this->createHandler->expects($this->never())->method('execute');
        $this->updateHandler->expects($this->once())->method('execute')->willReturn($entityData);
        $this->assertEquals(
            $entityData,
            $this->handler->execute(
                \Magento\Catalog\Api\Data\CategoryInterface::class,
                $entityData,
                ['copy_origin_in' => time()]
            )
        );
    }

    /**
     * @return array
     */
    public function versionProvider()
    {
        return [
            'version_of_new_entity' => [VersionManager::MIN_VERSION],
            'version_of_existing_entity' => [time()] // can be any integer greater than `version_of_new_entity`
        ];
    }
}
