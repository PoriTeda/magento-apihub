<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Test\Unit\Model\Operation;

use Magento\CatalogRuleStaging\Model\Operation\Create;

class CreateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $updateRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $operationUpdateMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $updateFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $operationCreateMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $updateMock;

    /**
     * @var \Magento\CatalogRuleStaging\Model\Operation\Create
     */
    private $operation;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $entityMock;

    protected function setUp()
    {
        $this->versionManagerMock = $this->createMock(\Magento\Staging\Model\VersionManager::class);
        $this->updateRepositoryMock = $this->createMock(\Magento\Staging\Api\UpdateRepositoryInterface::class);
        $this->operationUpdateMock = $this->createMock(\Magento\Staging\Model\Operation\Update::class);
        $this->updateFactoryMock = $this->createPartialMock(
            \Magento\Staging\Api\Data\UpdateInterfaceFactory::class,
            ['create']
        );
        $this->operationCreateMock = $this->createMock(\Magento\Staging\Model\Operation\Create::class);

        $this->updateMock = $this->createMock(\Magento\Staging\Api\Data\UpdateInterface::class);
        $this->entityMock = $this->createMock(\Magento\CatalogRule\Api\Data\RuleInterface::class);
        $this->operation = new Create(
            $this->versionManagerMock,
            $this->updateRepositoryMock,
            $this->operationUpdateMock,
            $this->updateFactoryMock,
            $this->operationCreateMock
        );
    }

    public function testExecute()
    {
        $name = 'rule';
        $id = 1;
        $updateId = 2;
        $updateMock = $this->createMock(\Magento\Staging\Api\Data\UpdateInterface ::class);
        $currentVersion = $this->createMock(\Magento\Staging\Api\Data\UpdateInterface ::class);
        //create update mock
        $this->updateFactoryMock->expects($this->once())->method('create')->willReturn($this->updateMock);
        $this->entityMock->expects($this->once())->method('getName')->willReturn($name);
        $this->updateMock->expects($this->once())->method('setName')->with($name);
        $this->updateMock->expects($this->once())->method('setIsCampaign')->with(false)->willReturnSelf();
        $this->updateMock->expects($this->once())
            ->method('setStartTime')
            ->willReturnSelf();
        $this->updateRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->updateMock)
            ->willReturn($this->updateMock);
        $this->updateMock->expects($this->once())->method('getId')->willReturn($id);
        $this->versionManagerMock->expects($this->any())->method('getVersion')->willReturn($updateMock);
        $this->versionManagerMock->expects($this->once())->method('getCurrentVersion')->willReturn($currentVersion);
        $updateMock->expects($this->any())->method('getId')->willReturn($updateId);
        //execute create operation
        $this->operationCreateMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->entityMock)
            ->willReturn($this->entityMock);
        $this->operationUpdateMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->entityMock)
            ->willReturn($this->entityMock);

        $this->assertEquals($this->entityMock, $this->operation->execute($this->entityMock));
    }
}
