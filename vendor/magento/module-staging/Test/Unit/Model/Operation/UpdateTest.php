<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model\Operation;

use Magento\Staging\Model\Operation\Update as UpdateOperation;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Staging\Model\ResourceModel\Db\ReadEntityVersion;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Staging\Model\Entity\Action\UpdateVersion;
use Magento\Staging\Model\Operation\Update\CreateEntityVersion;
use Magento\Staging\Model\Operation\Update\UpdateEntityVersion;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\EventManager;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\Operation\Update\RescheduleUpdate;
use Magento\Staging\Model\Operation\Update\CampaignIntegrity;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Staging\Api\Data\UpdateInterface;
use Magento\Staging\Model\VersionInfoProvider;

/**
 * Class UpdateTest
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateTest extends \PHPUnit\Framework\TestCase
{
    /** @var UpdateOperation|\PHPUnit_Framework_MockObject_MockObject */
    private $updateOperation;

    /** @var ReadEntityVersion|\PHPUnit_Framework_MockObject_MockObject */
    private $entityVersion;

    /** @var MetadataPool|\PHPUnit_Framework_MockObject_MockObject */
    private $metadataPool;

    /** @var UpdateVersion|\PHPUnit_Framework_MockObject_MockObject */
    private $updateVersion;

    /** @var CreateEntityVersion|\PHPUnit_Framework_MockObject_MockObject */
    private $createEntityVersion;

    /** @var UpdateEntityVersion|\PHPUnit_Framework_MockObject_MockObject */
    private $updateEntityVersion;

    /** @var ResourceConnection|\PHPUnit_Framework_MockObject_MockObject */
    private $resourceConnection;

    /** @var EventManager|\PHPUnit_Framework_MockObject_MockObject */
    private $eventManager;

    /** @var UpdateRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $updateRepository;

    /** @var RescheduleUpdate|\PHPUnit_Framework_MockObject_MockObject */
    private $rescheduleUpdate;

    /** @var CampaignIntegrity|\PHPUnit_Framework_MockObject_MockObject */
    private $campaignIntegrity;

    /** @var TypeResolver|\PHPUnit_Framework_MockObject_MockObject */
    private $typeResolver;

    /** @var  AdapterInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $adapter;

    /** @var \stdClass */
    private $entity;

    /** @var EntityMetadataInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $metadata;

    /** @var HydratorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $hydrator;

    /** @var UpdateInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $update;

    /** @var VersionInfoProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $versionInfoProvider;

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        $this->typeResolver = $this->getMockBuilder(TypeResolver::class)->disableOriginalConstructor()->getMock();
        $this->metadataPool = $this->getMockBuilder(MetadataPool::class)->disableOriginalConstructor()->getMock();
        $this->updateVersion = $this->getMockBuilder(UpdateVersion::class)->disableOriginalConstructor()->getMock();
        $this->eventManager = $this->getMockBuilder(EventManager::class)->disableOriginalConstructor()->getMock();
        $this->campaignIntegrity = $this->getMockBuilder(CampaignIntegrity::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rescheduleUpdate = $this->getMockBuilder(RescheduleUpdate::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateRepository = $this->getMockBuilder(UpdateRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->updateEntityVersion = $this->getMockBuilder(UpdateEntityVersion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->createEntityVersion = $this->getMockBuilder(CreateEntityVersion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityVersion = $this->getMockBuilder(ReadEntityVersion::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadata = $this->getMockBuilder(EntityMetadataInterface::class)
            ->getMock();
        $this->adapter = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->hydrator = $this->getMockBuilder(HydratorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entity = new \stdClass();
        $this->update = $this->getMockBuilder(UpdateInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionInfoProvider = $this->getMockBuilder(VersionInfoProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->updateOperation = $objectManagerHelper->getObject(
            UpdateOperation::class,
            [
                'typeResolver' => $this->typeResolver,
                'entityVersion' => $this->entityVersion,
                'metadataPool' => $this->metadataPool,
                'updateVersion' => $this->updateVersion,
                'createEntityVersion' => $this->createEntityVersion,
                'updateEntityVersion' => $this->updateEntityVersion,
                'resourceConnection' => $this->resourceConnection,
                'eventManager' => $this->eventManager,
                'updateRepository' => $this->updateRepository,
                'rescheduleUpdate' => $this->rescheduleUpdate,
                'campaignIntegrity' => $this->campaignIntegrity,
                'versionInfoProvider' => $this->versionInfoProvider
            ]
        );
    }

    /**
     * @dataProvider updateDataProvider
     * @param array $arguments
     * @param array $entityData
     * @return void
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testExecute($arguments = [], $entityData = [])
    {
        $rowId = $entityData['row_id'] ?? null;
        $rollBackId = $entityData['rollback_id'] ?? null;
        $identifier = $entityData['id'] ?? null;
        $entityType = PageInterface::class;

        $versionInfo = $this->getMockBuilder(\Magento\Staging\Model\VersionInfo::class)
            ->disableOriginalConstructor()
            ->getMock();
        $versionInfo->expects($this->any())
            ->method('getRowId')
            ->willReturn($rowId);
        $versionInfo->expects($this->any())
            ->method('getIdentifier')
            ->willReturn($identifier);
        $versionInfo->expects($this->any())
            ->method('getCreatedIn')
            ->willReturn($arguments['created_in'] ?? null);
        $versionInfo->expects($this->any())
            ->method('getUpdatedIn')
            ->willReturn($arguments['updated_in'] ?? null);
        $this->versionInfoProvider->expects($this->once())
            ->method('getVersionInfo')
            ->with($this->entity)
            ->willReturn($versionInfo);
        $this->typeResolver
            ->expects($this->once())
            ->method('resolve')
            ->with($this->entity)
            ->willReturn($entityType);
        $this->metadataPool->expects($this->once())
            ->method('getMetadata')
            ->willReturn($this->metadata);

        $connectionName = 'Connection';

        $this->metadata->expects($this->once())
            ->method('getEntityConnectionName')
            ->willReturn($connectionName);
        $this->metadata->expects($this->once())
            ->method('getIdentifierField')
            ->willReturn('id');
        $this->resourceConnection->expects($this->once())
            ->method('getConnectionByName')
            ->with($connectionName)
            ->willReturn($this->adapter);
        $this->adapter->expects($this->once())
            ->method('beginTransaction')
            ->willReturnSelf();
        $this->adapter->expects($this->never())
            ->method('rollBack')
            ->willReturnSelf();
        $this->metadataPool->expects($this->once())
            ->method('getHydrator')
            ->with($entityType)
            ->willReturn($this->hydrator);
        $this->hydrator
            ->method('extract')
            ->with($this->entity)
            ->willReturn($entityData);
        $this->eventManager->expects($this->at(0))
            ->method('dispatch')
            ->with('entity_save_before', ['entity_type' => $entityType, 'entity' => $this->entity]);
        $this->eventManager->expects($this->at(1))
            ->method('dispatchEntityEvent')
            ->with($entityType, 'save_before', ['entity' => $this->entity]);
        $this->updateRepository->expects($this->any())
            ->method('get')
            ->with($arguments['created_in'])
            ->willReturn($this->update);
        $this->update->expects($this->any())
            ->method('getRollbackId')
            ->willReturn($rollBackId);
        $this->eventManager->expects($this->at(2))
            ->method('dispatchEntityEvent')
            ->with($entityType, 'save_after', ['entity' => $this->entity]);
        $this->eventManager->expects($this->at(3))
            ->method('dispatch')
            ->with('entity_manager_save_after', ['entity_type' => $entityType, 'entity' => $this->entity]);

        $changedArguments = $arguments;

        if ($rollBackId && $rowId === null) {
            $changedArguments['updated_in'] = $rollBackId;
        } else {
            if (!$rowId) {
                $updateId = 999999999;
                $changedArguments['updated_in'] = 146469999;
                $this->update->expects($this->once())
                    ->method('getId')
                    ->willReturn($updateId);
                $this->entityVersion->expects($this->once())
                    ->method('getNextVersionId')
                    ->with($entityType, $updateId, $identifier)
                    ->willReturn($changedArguments['updated_in']);
            }
        }

        if ($rowId) {
            $this->metadata->expects($this->once())
                ->method('getLinkField')
                ->willReturn('row_id');

            if ($rollBackId) {
                $this->campaignIntegrity->expects($this->once())
                    ->method('createRollbackPoint')
                    ->with($this->update, $this->entity);
            } else {
                $this->updateEntityVersion->expects($this->once())
                    ->method('execute')
                    ->with($this->entity, array_merge($changedArguments, ['row_id' => $rowId]))
                    ->willReturn($this->entity);
            }
        } else {
            $this->updateVersion->expects($this->once())
                ->method('execute')
                ->with($entityType, $identifier);
            $this->createEntityVersion->expects($this->once())
                ->method('execute')
                ->with($this->entity, $changedArguments);
            $this->campaignIntegrity->expects($this->once())
                ->method('createRollbackPoint')
                ->with($this->update, $this->entity);
        }

        $this->campaignIntegrity->expects($this->once())
            ->method('synchronizeAffectedCampaigns')
            ->with($this->update, $this->entity);
        $this->adapter->expects($this->once())
            ->method('commit')
            ->willReturnSelf();

        $this->updateOperation->execute($this->entity, $arguments);
    }

    /**
     * Dataprovider fot update.
     *
     * @return array
     */
    public function updateDataProvider()
    {
        return [
            'testExecuteWithCreadtedInUpdatedInRefreshing' => [
                'arguments' => [
                    'created_in' => '1464685260',
                    'updated_in' => '1464685261',
                ],
                'entityData' => [
                    'id' => 1,
                    'row_id' => 2,
                ],
            ],
            'testExecuteWithoutCurrentRowIdAndWithRollbackId' => [
                'arguments' => [
                    'created_in' => '1464685260',
                    'updated_in' => '1464685261',
                ],
                'entityData' => [
                    'id' => 1,
                    'rollback_id' => '146469999',
                ],
            ],
            'testExecuteWithoutCurrentRowIdAndWithoutRollbackId' => [
                'arguments' => [
                    'created_in' => '1464685260',
                    'updated_in' => '1464685261',
                ],
                'entityData' => [
                    'id' => 1,
                ],
            ],
            'testExecuteForPermanentUpdateMadeTemporary' => [
                'arguments' => [
                    'created_in' => '1464685260',
                    'updated_in' => '1464685261',
                ],
                'entityData' => [
                    'id' => 1,
                    'row_id' => 2,
                    'rollback_id' => '146469999',
                ],
            ],
        ];
    }
}
