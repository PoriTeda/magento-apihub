<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\BundleStaging\Test\Unit\Model\EntityManager\Operation\Update;

use Magento\BundleStaging\Model\EntityManager\Operation\Update\CheckIfExists;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\HydratorPool;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DataObject;
use Magento\Framework\EntityManager\HydratorInterface;
use Magento\Framework\DB\Select;

/**
 * Class CheckIfExistsTest
 */
class CheckIfExistsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CheckIfExists
     */
    private $checkIfExists;

    /**
     * @var MetadataPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataPoolMock;

    /**
     * @var HydratorPool|\PHPUnit_Framework_MockObject_MockObject
     */
    private $hydratorPoolMock;

    /**
     * @var TypeResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    private $typeResolverMock;

    /**
     * @var ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resourceMock;

    /**
     * @var AdapterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $connectionMock;

    /**
     * @var DataObject|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityMock;

    /**
     * @var HydratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $hydratorMock;

    /**
     * @var EntityMetadataInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $metadataMock;

    /**
     * @var Select|\PHPUnit_Framework_MockObject_MockObject
     */
    private $selectMock;

    protected function setUp()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $entityType = \Magento\Bundle\Api\Data\OptionInterface::class;

        $this->metadataPoolMock = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->typeResolverMock = $this->getMockBuilder(TypeResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->hydratorPoolMock = $this->getMockBuilder(HydratorPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->connectionMock = $this->getMockBuilder(AdapterInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->selectMock = $this->getMockBuilder(Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionMock->expects($this->once())->method('select')->willReturn($this->selectMock);

        $this->entityMock = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->typeResolverMock->expects($this->once())
            ->method('resolve')
            ->with($this->entityMock)
            ->willReturn($entityType);

        $this->metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->with($entityType)
            ->willReturn($this->metadataMock);

        $this->hydratorMock = $this->getMockBuilder(HydratorInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->hydratorPoolMock->expects($this->once())
            ->method('getHydrator')
            ->with($entityType)
            ->willReturn($this->hydratorMock);

        $this->checkIfExists = $helper->getObject(
            CheckIfExists::class,
            [
                'typeResolver' => $this->typeResolverMock,
                'metadataPool' => $this->metadataPoolMock,
                'hydratorPool' => $this->hydratorPoolMock,
                'resourceConnection' => $this->resourceMock
            ]
        );
    }

    public function testExecute()
    {
        $primaryKeyName = 'primary_key_name';
        $primaryKeyValue = "col1";
        $indexList = [
            $primaryKeyName => ['COLUMNS_LIST' => [$primaryKeyValue]]
        ];
        $primaryKey  = $indexList[$primaryKeyName]['COLUMNS_LIST'];
        $entityData = [$primaryKeyValue => 'data'];
        $entityConnectionName = 'default';
        $entityTable = 'entity_table';

        $this->hydratorMock->expects($this->once())
            ->method('extract')
            ->with($this->entityMock)
            ->willReturn($entityData);
        $this->metadataMock->expects($this->once())
            ->method('getEntityConnectionName')
            ->willReturn($entityConnectionName);
        $this->resourceMock->expects($this->once())
            ->method('getConnectionByName')
            ->with($entityConnectionName)
            ->willReturn($this->connectionMock);
        $this->metadataMock->expects($this->any())->method('getEntityTable')->willReturn($entityTable);
        $this->connectionMock->expects($this->once())
            ->method('getIndexList')
            ->with($entityTable)
            ->willReturn($indexList);
        $this->connectionMock->expects($this->once())
            ->method('getPrimaryKeyName')
            ->with($entityTable)
            ->willReturn($primaryKeyName);
        $this->selectMock->expects($this->once())
            ->method('from')
            ->with($entityTable, $primaryKey)
            ->willReturnSelf();
        $this->selectMock->expects($this->once())
            ->method('where')
            ->with($primaryKeyValue . ' = ?', $entityData[$primaryKeyValue])
            ->willReturnSelf();
        $this->selectMock->expects($this->once())
            ->method('limit')
            ->with(1)
            ->willReturnSelf();
        $this->connectionMock->expects($this->once())->method('fetchOne')->with($this->selectMock)->willReturn(1);

        $this->assertTrue($this->checkIfExists->execute($this->entityMock));
    }

    public function testExecuteEntityDoesNotExist()
    {
        $primaryKeyName = 'primary_key_name';
        $indexList = [
            $primaryKeyName => ['COLUMNS_LIST' => ['col1']]
        ];
        $primaryKey  = $indexList[$primaryKeyName]['COLUMNS_LIST'];
        $entityData = ['col2' => 'data'];
        $entityConnectionName = 'default';
        $entityTable = 'entity_table';

        $this->hydratorMock->expects($this->once())
            ->method('extract')
            ->with($this->entityMock)
            ->willReturn($entityData);
        $this->metadataMock->expects($this->once())
            ->method('getEntityConnectionName')
            ->willReturn($entityConnectionName);
        $this->resourceMock->expects($this->once())
            ->method('getConnectionByName')
            ->with($entityConnectionName)
            ->willReturn($this->connectionMock);
        $this->metadataMock->expects($this->any())->method('getEntityTable')->willReturn($entityTable);
        $this->connectionMock->expects($this->once())
            ->method('getIndexList')
            ->with($entityTable)
            ->willReturn($indexList);
        $this->connectionMock->expects($this->once())
            ->method('getPrimaryKeyName')
            ->with($entityTable)
            ->willReturn($primaryKeyName);
        $this->selectMock->expects($this->once())
            ->method('from')
            ->with($entityTable, $primaryKey)
            ->willReturnSelf();

        $this->assertFalse($this->checkIfExists->execute($this->entityMock));
    }
}
