<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Test\Unit\Observer;

class ApplyPersistentDataObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $historyHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $persistentHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $customerSessionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configFactoryMock;

    /**
     * @var \Magento\PersistentHistory\Observer\ApplyPersistentDataObserver
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->historyHelperMock = $this->createMock(\Magento\PersistentHistory\Helper\Data::class);
        $this->sessionHelperMock = $this->createMock(\Magento\Persistent\Helper\Session::class);
        $this->customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);
        $this->configFactoryMock = $this->createPartialMock(
            \Magento\Persistent\Model\Persistent\ConfigFactory::class,
            ['create']
        );
        $this->persistentHelperMock = $this->createPartialMock(
            \Magento\Persistent\Helper\Data::class,
            ['isCompareProductsPersist', 'canProcess', '__wakeup']
        );

        $this->subject = $objectManager->getObject(
            \Magento\PersistentHistory\Observer\ApplyPersistentDataObserver::class,
            [
                'ePersistentData' => $this->historyHelperMock,
                'persistentSession' => $this->sessionHelperMock,
                'mPersistentData' => $this->persistentHelperMock,
                'customerSession' => $this->customerSessionMock,
                'configFactory' => $this->configFactoryMock
            ]
        );
    }

    public function testApplyPersistentDataIfDataCantProcess()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->will($this->returnValue(false));
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentDataIfSessionNotPersistent()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->will($this->returnValue(true));
        $this->sessionHelperMock->expects($this->once())->method('isPersistent')->will($this->returnValue(false));
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentDataIfUserLoggedIn()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->will($this->returnValue(true));
        $this->sessionHelperMock->expects($this->once())->method('isPersistent')->will($this->returnValue(true));
        $this->customerSessionMock->expects($this->once())->method('isLoggedIn')->will($this->returnValue(true));
        $this->subject->execute($observerMock);
    }

    public function testApplyPersistentDataSuccess()
    {
        $configFilePath = 'file/path';
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('canProcess')
            ->with($observerMock)
            ->will($this->returnValue(true));
        $this->sessionHelperMock->expects($this->once())->method('isPersistent')->will($this->returnValue(true));
        $this->customerSessionMock->expects($this->once())->method('isLoggedIn')->will($this->returnValue(false));

        $configMock = $this->createMock(\Magento\Persistent\Model\Persistent\Config::class);
        $configMock->expects($this->once())
            ->method('setConfigFilePath')
            ->with($configFilePath)
            ->will($this->returnSelf());

        $this->historyHelperMock->expects($this->once())
            ->method('getPersistentConfigFilePath')
            ->will($this->returnValue($configFilePath));

        $this->configFactoryMock->expects($this->once())->method('create')->will($this->returnValue($configMock));
        $this->subject->execute($observerMock);
    }
}
