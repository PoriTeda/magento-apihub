<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Test\Unit\Model\Event;

use Magento\Framework\Event\InvokerInterface;
use Magento\Framework\Event\ConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Staging\Model\VersionManager;

class ManagerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Event invoker
     *
     * @var InvokerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $invokerMock;

    /**
     * Event config
     *
     * @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventConfigMock;

    /**
     * @var \Magento\Staging\Model\VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    /**
     * @var \Magento\Staging\Model\VersionManagerFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerFactoryMock;

    protected function setUp()
    {
        $this->invokerMock = $this->getMockBuilder(InvokerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->eventConfigMock = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->versionManagerMock = $this->getMockBuilder(VersionManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->versionManagerFactoryMock = $this->getMockBuilder(\Magento\Staging\Model\VersionManagerFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();
        $this->versionManagerFactoryMock->expects($this->once())->method('create')->willReturn(
            $this->versionManagerMock
        );
    }

    public function testDispatchAllowed()
    {
        $eventData = ['entity' => new \stdClass()];
        $eventName = 'entity_save';
        $observerConfig = ['name' => 'observer'];
        $manager = new \Magento\Staging\Model\Event\Manager(
            $this->invokerMock,
            $this->eventConfigMock,
            $this->versionManagerFactoryMock
        );
        $this->eventConfigMock->expects($this->once())->method('getObservers')->with($eventName)->willReturn(
            [$observerConfig]
        );
        $event = new \Magento\Framework\Event($eventData);
        $event->setName($eventName);

        $wrapper = new Observer();
        $wrapper->setData(array_merge(['event' => $event], $eventData));
        $this->invokerMock->expects($this->once())->method('dispatch')->with($observerConfig, $wrapper);
        $manager->dispatch($eventName, $eventData);
    }

    public function testDispatchEventDisallowed()
    {
        $eventData = ['entity' => new \stdClass()];
        $eventName = 'entity_save';
        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);
        $manager = new \Magento\Staging\Model\Event\Manager(
            $this->invokerMock,
            $this->eventConfigMock,
            $this->versionManagerFactoryMock,
            [$eventName]
        );
        $this->eventConfigMock->expects($this->never())->method('getObservers');
        $this->invokerMock->expects($this->never())->method('dispatch');
        $manager->dispatch($eventName, $eventData);
    }

    public function testDispatchObserverDisallowed()
    {
        $eventData = ['entity' => new \stdClass()];
        $eventName = 'entity_save';
        $observerName = 'observer';
        $observerConfig = ['name' => $observerName];
        $this->versionManagerMock->expects($this->once())->method('isPreviewVersion')->willReturn(true);
        $manager = new \Magento\Staging\Model\Event\Manager(
            $this->invokerMock,
            $this->eventConfigMock,
            $this->versionManagerFactoryMock,
            [],
            [$eventName => [$observerName]]
        );
        $this->eventConfigMock->expects($this->once())->method('getObservers')->with($eventName)->willReturn(
            [$observerConfig]
        );
        $this->invokerMock->expects($this->never())->method('dispatch');
        $manager->dispatch($eventName, $eventData);
    }
}
