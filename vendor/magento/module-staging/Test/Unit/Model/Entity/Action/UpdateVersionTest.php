<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Entity\Action;

use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\Model\ResourceModel\Db\UpdateEntityRow;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\Entity\Action\UpdateVersion;
use Magento\Staging\Model\VersionManager\Proxy as VersionManager;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 *
 * Class UpdateVersionTest
 */
class UpdateVersionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var UpdateVersion
     */
    private $updateVersion;

    /**
     * @var UpdateEntityRow|\PHPUnit_Framework_MockObject_MockObject
     */
    private $updateEntityRowMock;

    /**
     * @var MetadataPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataPoolMock;

    /**
     * @var ReadEntityVersion|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityVersionMock;

    /**
     * @var VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    /**
     * @var UpdateInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionMock;

    /**
     * @var EntityMetadataInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataMock;

    protected function setUp()
    {
        $this->updateEntityRowMock = $this->getMockBuilder(UpdateEntityRow::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPoolMock = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityVersionMock = $this->getMockBuilder(ReadEntityVersion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->setMethods(['getVersion'])
            ->getMock();
        $this->versionMock = $this->getMockBuilder(UpdateInterface::class)->getMockForAbstractClass();
        $this->metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)->getMockForAbstractClass();
        $this->updateVersion = new UpdateVersion(
            $this->updateEntityRowMock,
            $this->metadataPoolMock,
            $this->entityVersionMock,
            $this->versionManagerMock
        );
    }

    public function testExecute()
    {
        $entityType = \Magento\Catalog\Api\Data\ProductInterface::class;
        $identifier = 1;
        $previousRowId = 4;
        $currentVersionId = 232232332;
        $linkField = 'row_id';
        $this->metadataPoolMock->expects($this->once())->method('getMetadata')->with($entityType)->willReturn(
            $this->metadataMock
        );
        $this->metadataMock->expects($this->once())->method('getLinkField')->willReturn($linkField);
        $this->entityVersionMock->expects($this->once())
            ->method('getPreviousVersionRowId')
            ->with($entityType, $identifier)
            ->willReturn($previousRowId);
        $this->versionManagerMock->expects($this->once())->method('getVersion')->willReturn(
            $this->versionMock
        );
        $this->versionMock->expects($this->once())->method('getId')->willReturn($currentVersionId);
        $this->updateEntityRowMock->expects($this->once())->method('execute')->with(
            $entityType,
            [
                $linkField => $previousRowId,
                'updated_in' => $currentVersionId
            ]
        );

        $this->updateVersion->execute($entityType, $identifier);
    }
}
