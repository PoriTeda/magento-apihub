<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Test\Unit\Observer;

class ApplyCustomerIdObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $historyHelperMoc;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $persistentHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionHelperMock;

    /**
     * @var \Magento\PersistentHistory\Observer\ApplyCustomerIdObserver
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->historyHelperMoc = $this->createMock(\Magento\PersistentHistory\Helper\Data::class);
        $this->sessionHelperMock = $this->createMock(\Magento\Persistent\Helper\Session::class);
        $this->persistentHelperMock = $this->createPartialMock(
            \Magento\Persistent\Helper\Data::class,
            ['isCompareProductsPersist', 'canProcess', '__wakeup']
        );

        $this->subject = $objectManager->getObject(
            \Magento\PersistentHistory\Observer\ApplyCustomerIdObserver::class,
            [
                'ePersistentData' => $this->historyHelperMoc,
                'persistentSession' => $this->sessionHelperMock,
                'mPersistentData' => $this->persistentHelperMock
            ]
        );
    }

    public function testApplyPersistentCustomerIdIfPersistentDataCantProcess()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->will($this->returnValue(false));
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentCustomerIdIfCannotCompareProduct()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->will($this->returnValue(true));
        $this->historyHelperMoc->expects($this->once())
            ->method('isCompareProductsPersist')
            ->will($this->returnValue(false));
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentCustomerIdSuccess()
    {
        $customerId = 1;
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->will($this->returnValue(true));
        $this->historyHelperMoc->expects($this->once())
            ->method('isCompareProductsPersist')
            ->will($this->returnValue(true));

        $actionMock = $this->createPartialMock(
            \Magento\Framework\App\Test\Unit\Action\Stub\ActionStub::class,
            ['setCustomerId']
        );
        $actionMock->expects($this->once())->method('setCustomerId')->with($customerId)->will($this->returnSelf());

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getControllerAction']);
        $eventMock->expects($this->once())
            ->method('getControllerAction')
            ->will($this->returnValue($actionMock));

        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $sessionMock = $this->createPartialMock(
            \Magento\Persistent\Model\Session::class,
            ['getCustomerId', '__wakeup']
        );
        $sessionMock->expects($this->once())->method('getCustomerId')->will($this->returnValue($customerId));
        $this->sessionHelperMock->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($sessionMock));

        $this->subject->execute($observerMock);
    }
}
