<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Test\Unit\Observer;

class ApplyBlockPersistentDataObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $persistentHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $observerMock;

    /**
     * @var \Magento\PersistentHistory\Observer\ApplyBlockPersistentDataObserver
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->persistentHelperMock = $this->createMock(\Magento\PersistentHistory\Helper\Data::class);
        $this->observerMock = $this->createMock(\Magento\Persistent\Observer\ApplyBlockPersistentDataObserver::class);

        $this->subject = $objectManager->getObject(
            \Magento\PersistentHistory\Observer\ApplyBlockPersistentDataObserver::class,
            [
                'ePersistentData' => $this->persistentHelperMock,
                'observer' => $this->observerMock,
            ]
        );
    }

    public function testApplyBlockPersistentData()
    {
        $configFilePath = 'file/path';
        $eventObserverMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['setConfigFilePath']);
        $eventMock->expects($this->once())
            ->method('setConfigFilePath')
            ->with($configFilePath)
            ->will($this->returnSelf());

        $eventObserverMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $this->persistentHelperMock->expects($this->once())
            ->method('getPersistentConfigFilePath')
            ->will($this->returnValue($configFilePath));

        $this->observerMock->expects($this->once())
            ->method('execute')
            ->with($eventObserverMock)
            ->will($this->returnSelf());

        $this->assertEquals($this->observerMock, $this->subject->execute($eventObserverMock));
    }
}
