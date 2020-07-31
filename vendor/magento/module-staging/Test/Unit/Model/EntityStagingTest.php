<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Model;

use Magento\Framework\EntityManager\TypeResolver;
use Magento\Framework\ObjectManagerInterface;
use Magento\Staging\Model\EntityStaging;

/**
 * Class EntityStagingTest
 */
class EntityStagingTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EntityStaging|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityStagingMock;

    /**
     * @var ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectManagerMock;

    /**
     * @var TypeResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    private $typeResolverMock;

    /**
     * @var array
     */
    private $types = [
        'Test/TestType' => 'Result/StagingInterface'
    ];

    /**
     * @var EntityStaging
     */
    private $model;

    protected function setUp()
    {
        $this->objectManagerMock = $this->getMockBuilder(
            ObjectManagerInterface::class
        )->getMockForAbstractClass();
        $this->typeResolverMock = $this->getMockBuilder(TypeResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityStagingMock = $this->getMockBuilder(EntityStaging::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManager->getObject(
            EntityStaging::class,
            [
                'objectManager' => $this->objectManagerMock,
                'typeResolver' => $this->typeResolverMock,
                'stagingServices' => $this->types
            ]
        );
    }

    public function testSchedule()
    {
        $entity = new \stdClass();
        $this->expectTypeResolving($entity, key($this->types));
        $this->objectManagerMock->expects($this->once())->method('get')->with(current($this->types))->willReturn(
            $this->entityStagingMock
        );
        $this->entityStagingMock->expects($this->once())->method('schedule')->willReturn(true);
        $this->assertTrue($this->model->schedule($entity, 1));
    }

    /**
     * @expectedException \Magento\Framework\Exception\ConfigurationMismatchException
     */
    public function testScheduleConfigurationMismatch()
    {
        $entity = new \stdClass();
        $this->expectTypeResolving($entity, 'unknowntype');
        $this->objectManagerMock->expects($this->never())->method('get');
        $this->entityStagingMock->expects($this->never())->method('schedule');
        $this->assertTrue($this->model->schedule($entity, 1));
    }

    public function testUnSchedule()
    {
        $entity = new \stdClass();
        $this->expectTypeResolving($entity, key($this->types));
        $this->objectManagerMock->expects($this->once())->method('get')->with(current($this->types))->willReturn(
            $this->entityStagingMock
        );
        $this->entityStagingMock->expects($this->once())->method('unschedule')->willReturn(true);
        $this->assertTrue($this->model->unschedule($entity, 1));
    }

    /**
     * @expectedException \Magento\Framework\Exception\ConfigurationMismatchException
     */
    public function testUnScheduleConfigurationMismatch()
    {
        $entity = new \stdClass();
        $this->expectTypeResolving($entity, 'unknowntype');
        $this->objectManagerMock->expects($this->never())->method('get');
        $this->entityStagingMock->expects($this->never())->method('unschedule');
        $this->assertTrue($this->model->schedule($entity, 1));
    }

    /**
     * @param object $entity
     * @param string $resultingType
     * @return void
     */
    private function expectTypeResolving($entity, $resultingType)
    {
        $this->typeResolverMock->expects($this->once())->method('resolve')->with($entity)->willReturn($resultingType);
    }
}
