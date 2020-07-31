<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Test\Unit\Model\Mview\View\Attribute;

use Magento\CatalogStaging\Model\Mview\View\Attribute\Subscription as SubscriptionModel;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Ddl\TriggerFactory;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Mview\View\CollectionInterface;
use Magento\Framework\Mview\View\StateInterface;
use Magento\Framework\Mview\ViewInterface;

/**
 * Class SubscriptionTest - unit test for attribute subscription model
 * @package Magento\CatalogStaging\Test\Unit\Model\Mview\View\Attribute
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SubscriptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Mysql PDO DB adapter mock
     *
     * @var \PHPUnit_Framework_MockObject_MockObject|Mysql
     */
    protected $connectionMock;

    /**
     * @var SubscriptionModel
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ResourceConnection
     */
    protected $resourceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TriggerFactory
     */
    protected $triggerFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CollectionInterface
     */
    protected $viewCollectionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ViewInterface
     */
    protected $viewMock;

    /**
     * @var string
     */
    private $tableName;

    /**
     * @var EntityMetadataInterface
     */
    private $entityMetadataMock;

    /**
     * @var MetadataPool
     */
    private $entityMetadataPoolMock;

    protected function setUp()
    {
        $this->connectionMock = $this->createMock(Mysql::class);
        $this->resourceMock = $this->createMock(ResourceConnection::class);
        $this->connectionMock->expects($this->any())
            ->method('quoteIdentifier')
            ->will($this->returnArgument(0));
        $this->resourceMock->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($this->connectionMock);
        $this->triggerFactoryMock = $this->createMock(TriggerFactory::class);
        $this->viewCollectionMock = $this->getMockForAbstractClass(
            CollectionInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $this->viewMock = $this->getMockForAbstractClass(ViewInterface::class, [], '', false, false, true, []);
        $this->resourceMock->expects($this->any())
            ->method('getTableName')
            ->will($this->returnArgument(0));

        $entityInterface = 'EntityInterface';
        $this->entityMetadataPoolMock = $this->createMock(MetadataPool::class);

        $this->entityMetadataMock = $this->createMock(EntityMetadataInterface::class);
        $this->entityMetadataMock->expects($this->any())
            ->method('getEntityTable')
            ->will($this->returnValue('entity_table'));

        $this->entityMetadataMock->expects($this->any())
            ->method('getIdentifierField')
            ->will($this->returnValue('entity_identifier'));

        $this->entityMetadataMock->expects($this->any())
            ->method('getLinkField')
            ->will($this->returnValue('entity_link_field'));

        $this->entityMetadataPoolMock->expects($this->any())
            ->method('getMetadata')
            ->with($entityInterface)
            ->will($this->returnValue($this->entityMetadataMock));

        $this->model = new SubscriptionModel(
            $this->resourceMock,
            $this->triggerFactoryMock,
            $this->viewCollectionMock,
            $this->viewMock,
            $this->tableName,
            'columnName',
            $this->entityMetadataPoolMock,
            $entityInterface
        );
    }

    /**
     * Prepare trigger mock
     *
     * @param string $triggerName
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareTriggerMock($triggerName)
    {
        $triggerMock = $this->getMockBuilder(\Magento\Framework\DB\Ddl\Trigger::class)
            ->setMethods(['setName', 'getName', 'setTime', 'setEvent', 'setTable', 'addStatement'])
            ->disableOriginalConstructor()
            ->getMock();
        $triggerMock->expects($this->exactly(3))
            ->method('setName')
            ->with($triggerName)
            ->will($this->returnSelf());
        $triggerMock->expects($this->exactly(3))
            ->method('getName')
            ->will($this->returnValue('triggerName'));
        $triggerMock->expects($this->exactly(3))
            ->method('setTime')
            ->with(\Magento\Framework\DB\Ddl\Trigger::TIME_AFTER)
            ->will($this->returnSelf());
        $triggerMock->expects($this->exactly(3))
            ->method('setEvent')
            ->will($this->returnSelf());
        $triggerMock->expects($this->exactly(3))
            ->method('setTable')
            ->with($this->tableName)
            ->will($this->returnSelf());
        return $triggerMock;
    }

    /**
     * Prepare expected trigger call map
     *
     * @param \PHPUnit_Framework_MockObject_MockObject $triggerMock
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareTriggerTestCallMap(\PHPUnit_Framework_MockObject_MockObject $triggerMock)
    {
        $triggerMock->expects($this->at(4))
            ->method('addStatement')
            ->with(
                "SET @entity_id = (SELECT entity_identifier FROM entity_table "
                . "WHERE entity_link_field = NEW.entity_link_field);\n"
                . "INSERT IGNORE INTO test_view_cl (entity_id) values(@entity_id);"
            )
            ->will($this->returnSelf());

        $triggerMock->expects($this->at(5))
            ->method('addStatement')
            ->with(
                "INSERT IGNORE INTO other_test_view_cl (entity_id) values(@entity_id);"
            )->will($this->returnSelf());

        $triggerMock->expects($this->at(11))
            ->method('addStatement')
            ->with(
                "SET @entity_id = (SELECT entity_identifier FROM entity_table "
                . "WHERE entity_link_field = NEW.entity_link_field);\n"
                . "INSERT IGNORE INTO test_view_cl (entity_id) values(@entity_id);"
            )->will($this->returnSelf());

        $triggerMock->expects($this->at(12))
            ->method('addStatement')
            ->with(
                "INSERT IGNORE INTO other_test_view_cl (entity_id) values(@entity_id);"
            )->will($this->returnSelf());

        $triggerMock->expects($this->at(18))
            ->method('addStatement')
            ->with(
                "SET @entity_id = (SELECT entity_identifier FROM entity_table "
                . "WHERE entity_link_field = OLD.entity_link_field);\n"
                . "INSERT IGNORE INTO test_view_cl (entity_id) values(@entity_id);"
            )->will($this->returnSelf());

        $triggerMock->expects($this->at(19))
            ->method('addStatement')
            ->with(
                "INSERT IGNORE INTO other_test_view_cl (entity_id) values(@entity_id);"
            )->will($this->returnSelf());

        return $triggerMock;
    }

    /**
     * Prepare changelog mock
     *
     * @param string $changelogName
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function prepareChangelogMock($changelogName)
    {
        $changelogMock = $this->getMockForAbstractClass(
            \Magento\Framework\Mview\View\ChangelogInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $changelogMock->expects($this->exactly(3))
            ->method('getName')
            ->will($this->returnValue($changelogName));
        $changelogMock->expects($this->exactly(3))
            ->method('getColumnName')
            ->will($this->returnValue('entity_id'));
        return $changelogMock;
    }

    public function testCreate()
    {
        $triggerName = 'trigger_name';
        $this->resourceMock->expects($this->atLeastOnce())->method('getTriggerName')->willReturn($triggerName);
        $triggerMock = $this->prepareTriggerMock($triggerName);
        $this->prepareTriggerTestCallMap($triggerMock);
        $changelogMock = $this->prepareChangelogMock('test_view_cl');

        $this->viewMock->expects($this->exactly(3))
            ->method('getChangelog')
            ->will($this->returnValue($changelogMock));

        $this->triggerFactoryMock->expects($this->exactly(3))
            ->method('create')
            ->will($this->returnValue($triggerMock));

        $otherChangelogMock = $this->prepareChangelogMock('other_test_view_cl');

        $otherViewMock = $this->getMockForAbstractClass(
            ViewInterface::class,
            [],
            '',
            false,
            false,
            true,
            []
        );
        $otherViewMock->expects($this->exactly(1))
            ->method('getId')
            ->will($this->returnValue('other_id'));
        $otherViewMock->expects($this->exactly(1))
            ->method('getSubscriptions')
            ->will($this->returnValue([['name' => $this->tableName], ['name' => 'otherTableName']]));
        $otherViewMock->expects($this->any())
            ->method('getChangelog')
            ->will($this->returnValue($otherChangelogMock));

        $this->viewMock->expects($this->exactly(3))
            ->method('getId')
            ->will($this->returnValue('this_id'));
        $this->viewMock->expects($this->never())
            ->method('getSubscriptions');

        $this->viewCollectionMock->expects($this->exactly(1))
            ->method('getViewsByStateMode')
            ->with(StateInterface::MODE_ENABLED)
            ->will($this->returnValue([$this->viewMock, $otherViewMock]));

        $this->connectionMock->expects($this->exactly(3))
            ->method('dropTrigger')
            ->with('triggerName')
            ->will($this->returnValue(true));
        $this->connectionMock->expects($this->exactly(3))
            ->method('createTrigger')
            ->with($triggerMock);

        $this->model->create();
    }
}
