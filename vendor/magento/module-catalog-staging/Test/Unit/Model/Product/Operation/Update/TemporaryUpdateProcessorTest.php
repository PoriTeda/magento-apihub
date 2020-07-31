<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Model\Product\Operation\Update;

use Magento\CatalogStaging\Model\Product\Operation\Update\TemporaryUpdateProcessor;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion as EntityVersion;
use Magento\Staging\Model\Operation\Update\CreateEntityVersion;
use Magento\Staging\Model\Entity\Builder;
use Magento\Catalog\Controller\Adminhtml\Product\Initialization\Helper as InitializationHelper;
use Magento\Catalog\Api\ProductLinkRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TemporaryUpdateProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $entityManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $entityVersionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $createVersionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $entityBuilderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $initializationHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $productLinkRepositoryMock;

    /**
     * @var \Magento\CatalogStaging\Model\Product\Operation\Update\TemporaryUpdateProcessor
     */
    private $model;

    protected function setUp()
    {
        $this->entityManagerMock = $this->createMock(EntityManager::class);
        $this->versionManagerMock = $this->createMock(VersionManager::class);
        $this->entityVersionMock = $this->createMock(EntityVersion::class);
        $this->createVersionMock = $this->createMock(CreateEntityVersion::class);
        $this->entityBuilderMock = $this->createMock(Builder::class);
        $this->initializationHelperMock = $this->createMock(InitializationHelper::class);
        $this->productLinkRepositoryMock = $this->getMockForAbstractClass(
            ProductLinkRepositoryInterface::class,
            [],
            '',
            false,
            false
        );
        $this->model = new TemporaryUpdateProcessor(
            $this->entityManagerMock,
            $this->versionManagerMock,
            $this->entityVersionMock,
            $this->createVersionMock,
            $this->entityBuilderMock,
            $this->initializationHelperMock,
            $this->productLinkRepositoryMock
        );
    }

    public function testProcess()
    {
        $versionId = 1396569600;
        $rollbackId = 1365033600;
        $previousVersion = 1386569600;
        $entityId = "42";

        $productLinkMock = $this->createMock(\Magento\Catalog\Model\ProductLink\Link::class);

        $entityMock = $this->getMockBuilder(Product::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'getProductLinks',
                'setProductLinks',
                'getId',
                'setId',
                'unsetData',
            ])
            ->getMock();
        $entityMock->expects($this->once())->method('getProductLinks')->willReturn([$productLinkMock]);
        $entityMock->expects($this->once())->method('setProductLinks');
        $entityMock->expects($this->atLeastOnce())->method('getId')->willReturn($entityId);
        $entityMock->expects($this->once())
            ->method('unsetData')
            ->willReturnSelf();
        $entityMock->expects($this->once())
            ->method('setId')
            ->with($entityId)
            ->willReturnSelf();

        $this->entityVersionMock->expects($this->once())->method('getPreviousVersionId')
            ->with(ProductInterface::class, $versionId, $entityId)->willReturn($previousVersion);
        $this->entityVersionMock->expects($this->once())->method('getNextVersionId')
            ->with(ProductInterface::class, $rollbackId, $entityId)->willReturn($previousVersion);
        $this->versionManagerMock->expects($this->atLeastOnce())->method('setCurrentVersionId')
            ->withConsecutive([$previousVersion], [$rollbackId], [$versionId]);
        $this->entityManagerMock->expects($this->once())->method('load');
        $this->entityBuilderMock->expects($this->once())->method('build')->willReturn($entityMock);
        $this->initializationHelperMock
            ->expects($this->never())
            ->method('initialize');
        $this->createVersionMock->expects($this->once())->method('execute');
        $this->productLinkRepositoryMock->expects($this->once())->method('save')->with($productLinkMock)
            ->willReturn(true);

        $this->assertSame($entityMock, $this->model->process($entityMock, $versionId, $rollbackId));
    }

    public function testBuildEntity()
    {
        $objectManager = new ObjectManager($this);
        $entityMock = $objectManager->getObject(\Magento\Catalog\Model\Product::class, ['row_id' => 10]);

        $this->entityBuilderMock->expects($this->once())->method('build')->with($entityMock)->willReturn($entityMock);
        $this->initializationHelperMock
            ->expects($this->never())
            ->method('initialize');

        $this->model->buildEntity($entityMock);
        $this->assertNull($entityMock->getRowId());
    }

    public function testLoadEntity()
    {
        $entityId = "42";
        $entityMock = $this->createPartialMock(\Magento\Framework\DataObject::class, ['setProductLinks', 'getId']);
        $entityMock->expects($this->once())->method('setProductLinks');
        $entityMock->expects($this->once())->method('getId')->willReturn($entityId);

        $this->entityManagerMock->expects($this->once())->method('load')->with($entityMock, $entityId);
        $this->model->loadEntity($entityMock);
    }
}
