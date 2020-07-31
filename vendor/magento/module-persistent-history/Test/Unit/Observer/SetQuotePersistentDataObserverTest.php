<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PersistentHistory\Test\Unit\Observer;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\PersistentHistory\Observer\SetQuotePersistentDataObserver;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SetQuotePersistentDataObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerRepository;

    /**
     * @var \Magento\Persistent\Helper\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $persistentSession;

    /**
     * @var \Magento\PersistentHistory\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $persistentHistoryDataHelper;

    /**
     * @var \Magento\Persistent\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $persistentDataHelper;

    /**
     * @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSession;

    /**
     * @var \Magento\PersistentHistory\Observer\QuotePersistentPreventFlag|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quotePersistent;

    /**
     * @var SetQuotePersistentDataObserver
     */
    private $observer;

    /**
     * @var \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventObserver;

    /**
     * @var \Magento\Framework\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var \Magento\Quote\Api\Data\CartInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $quote;

    protected function setUp()
    {
        $this->quote = $this->getMockBuilder(\Magento\Quote\Api\Data\CartInterface::class)
            ->setMethods(['setCustomer'])
            ->getMockForAbstractClass();
        $this->event = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->setMethods(['getQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventObserver = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->setMethods(['getEvent'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->persistentSession = $this->getMockBuilder(\Magento\Persistent\Helper\Session::class)
            ->setMethods(['isPersistent'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->persistentHistoryDataHelper = $this->getMockBuilder(\Magento\PersistentHistory\Helper\Data::class)
            ->setMethods(['isCustomerAndSegmentsPersist'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->persistentDataHelper = $this->getMockBuilder(
            \Magento\Persistent\Helper\Data::class
        )
            ->setMethods(['canProcess'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerSession = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->setMethods(['getCustomerId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quotePersistent = $this->getMockBuilder(
            \Magento\PersistentHistory\Observer\QuotePersistentPreventFlag::class
        )
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerRepository = $this->getMockBuilder(\Magento\Customer\Api\CustomerRepositoryInterface::class)
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->observer = (new ObjectManager($this))->getObject(
            SetQuotePersistentDataObserver::class,
            [
                'persistentSession' => $this->persistentSession,
                'persistentHistoryDataHelper' => $this->persistentHistoryDataHelper,
                'persistentDataHelper' => $this->persistentDataHelper,
                'customerSession' => $this->customerSession,
                'quotePersistent' => $this->quotePersistent,
                'customerRepository' => $this->customerRepository,
                'logger' => $this->logger,
            ]
        );
    }

    public function testUnprocessableEvent()
    {
        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(false);
        $this->persistentSession->expects($this->never())->method('isPersistent');
        $this->eventObserver->expects($this->never())->method('getEvent');
        $this->event->expects($this->never())->method('getQuote');
        $this->quote->expects($this->never())->method('setCustomer');
        $this->persistentHistoryDataHelper->expects($this->never())->method('isCustomerAndSegmentsPersist');
        $this->quotePersistent->expects($this->never())->method('getValue');
        $this->customerSession->expects($this->never())->method('getCustomerId');
        $this->customerRepository->expects($this->never())->method('getById');
        $this->observer->execute($this->eventObserver);
    }

    public function testNotPersistableSession()
    {
        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(true);
        $this->persistentSession->expects($this->once())->method('isPersistent')->willReturn(false);
        $this->eventObserver->expects($this->never())->method('getEvent');
        $this->event->expects($this->never())->method('getQuote');
        $this->quote->expects($this->never())->method('setCustomer');
        $this->persistentHistoryDataHelper->expects($this->never())->method('isCustomerAndSegmentsPersist');
        $this->quotePersistent->expects($this->never())->method('getValue');
        $this->customerSession->expects($this->never())->method('getCustomerId');
        $this->customerRepository->expects($this->never())->method('getById');
        $this->observer->execute($this->eventObserver);
    }

    public function testMissingQuote()
    {
        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(true);
        $this->persistentSession->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->eventObserver->expects($this->once())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->once())->method('getQuote')->willReturn(null);
        $this->quote->expects($this->never())->method('setCustomer');
        $this->persistentHistoryDataHelper->expects($this->never())->method('isCustomerAndSegmentsPersist');
        $this->quotePersistent->expects($this->never())->method('getValue');
        $this->customerSession->expects($this->never())->method('getCustomerId');
        $this->customerRepository->expects($this->never())->method('getById');
        $this->observer->execute($this->eventObserver);
    }

    public function testCustomerPersistanceDisabled()
    {
        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(true);
        $this->persistentSession->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->eventObserver->expects($this->once())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->never())->method('setCustomer');
        $this->persistentHistoryDataHelper->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->with(null)
            ->willReturn(false);
        $this->quotePersistent->expects($this->never())->method('getValue');
        $this->customerSession->expects($this->never())->method('getCustomerId');
        $this->customerRepository->expects($this->never())->method('getById');
        $this->observer->execute($this->eventObserver);
    }

    public function testQuotePersistanceDisabled()
    {
        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(true);
        $this->persistentSession->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->eventObserver->expects($this->once())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->never())->method('setCustomer');
        $this->persistentHistoryDataHelper->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->with(null)
            ->willReturn(true);
        $this->quotePersistent->expects($this->once())->method('getValue')->willReturn(false);
        $this->customerSession->expects($this->never())->method('getCustomerId');
        $this->customerRepository->expects($this->never())->method('getById');
        $this->observer->execute($this->eventObserver);
    }

    public function testAnonymousCustomer()
    {
        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(true);
        $this->persistentSession->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->eventObserver->expects($this->once())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->quote->expects($this->never())->method('setCustomer');
        $this->persistentHistoryDataHelper->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->with(null)
            ->willReturn(true);
        $this->quotePersistent->expects($this->once())->method('getValue')->willReturn(true);
        $this->customerSession->expects($this->once())->method('getCustomerId')->willReturn(null);
        $this->customerRepository->expects($this->never())->method('getById');
        $this->observer->execute($this->eventObserver);
    }

    public function testUnknownCustomer()
    {
        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(true);
        $this->persistentSession->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->eventObserver->expects($this->once())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->persistentHistoryDataHelper->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->with(null)
            ->willReturn(true);
        $this->quotePersistent->expects($this->once())->method('getValue')->willReturn(true);
        $this->customerSession->expects($this->once())->method('getCustomerId')->willReturn(123);
        $this->customerRepository->expects($this->once())->method('getById')->willThrowException(
            NoSuchEntityException::singleField('customer_id', 123)
        );
        $this->logger->expects($this->once())->method('notice');
        $this->quote->expects($this->never())->method('setCustomer');
        $this->observer->execute($this->eventObserver);
    }

    public function testCustomerHasBeenSetToQuote()
    {
        $customer = $this->getMockBuilder(\Magento\Customer\Api\Data\CustomerInterface::class)
            ->setMethods([])
            ->getMockForAbstractClass();

        $this->persistentDataHelper->expects($this->once())->method('canProcess')->willReturn(true);
        $this->persistentSession->expects($this->once())->method('isPersistent')->willReturn(true);
        $this->eventObserver->expects($this->once())->method('getEvent')->willReturn($this->event);
        $this->event->expects($this->once())->method('getQuote')->willReturn($this->quote);
        $this->persistentHistoryDataHelper->expects($this->once())
            ->method('isCustomerAndSegmentsPersist')
            ->with(null)
            ->willReturn(true);
        $this->quotePersistent->expects($this->once())->method('getValue')->willReturn(true);
        $this->customerSession->expects($this->once())->method('getCustomerId')->willReturn(12);
        $this->customerRepository->expects($this->once())->method('getById')->with(12)->willReturn($customer);
        $this->logger->expects($this->never())->method('notice');
        $this->quote->expects($this->once())->method('setCustomer')->with($customer);
        $this->observer->execute($this->eventObserver);
    }
}
