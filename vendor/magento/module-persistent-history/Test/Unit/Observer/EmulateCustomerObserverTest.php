<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Test\Unit\Observer;

class EmulateCustomerObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $ePersistentDataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $mPersistentDataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSessionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $persistentSessionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $emulatorMock;

    /**
     * @var \Magento\PersistentHistory\Observer\EmulateCustomerObserver
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->ePersistentDataMock = $this->createPartialMock(
            \Magento\PersistentHistory\Helper\Data::class,
            ['isCustomerAndSegmentsPersist']
        );
        $this->persistentSessionMock = $this->createMock(\Magento\Persistent\Helper\Session::class);

        $this->customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->emulatorMock = $this->createMock(\Magento\PersistentHistory\Model\CustomerEmulator::class);
        $this->mPersistentDataMock = $this->createPartialMock(
            \Magento\Persistent\Helper\Data::class,
            ['canProcess', '__wakeup']
        );

        $this->subject = $objectManager->getObject(
            \Magento\PersistentHistory\Observer\EmulateCustomerObserver::class,
            [
                'ePersistentData' => $this->ePersistentDataMock,
                'persistentSession' => $this->persistentSessionMock,
                'mPersistentData' => $this->mPersistentDataMock,
                'customerSession' => $this->customerSessionMock,
                'customerEmulator' => $this->emulatorMock
            ]
        );
    }

    public function testSetPersistentDataIfDataCannotBeProcessed()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->mPersistentDataMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->will($this->returnValue(false));
        $this->subject->execute($observerMock);
    }

    public function testSetPersistentDataIfCustomerIsNotPersistent()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->mPersistentDataMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->will($this->returnValue(true));
        $this->ePersistentDataMock->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->will($this->returnValue(false));
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentDataIfSessionNotPersistent()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->mPersistentDataMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->will($this->returnValue(true));
        $this->ePersistentDataMock->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->will($this->returnValue(true));
        $this->persistentSessionMock->expects($this->once())->method('isPersistent')->will($this->returnValue(false));
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentDataIfUserLoggedIn()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->mPersistentDataMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->will($this->returnValue(true));
        $this->ePersistentDataMock->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->will($this->returnValue(true));
        $this->persistentSessionMock->expects($this->once())->method('isPersistent')->will($this->returnValue(true));
        $this->customerSessionMock->expects($this->once())->method('isLoggedIn')->will($this->returnValue(true));
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentDataSuccess()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->mPersistentDataMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->will($this->returnValue(true));
        $this->ePersistentDataMock->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->will($this->returnValue(true));
        $this->persistentSessionMock->expects($this->once())->method('isPersistent')->will($this->returnValue(true));
        $this->customerSessionMock->expects($this->once())->method('isLoggedIn')->will($this->returnValue(false));
        $this->emulatorMock->expects($this->once())->method('emulate');
        $this->subject->execute($observerMock);
    }
}
