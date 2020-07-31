<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Observer;

class ProcessOrderCreationDataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardDataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $importerMock;

    /**
     * @var \Magento\Reward\Observer\ProcessOrderCreationData
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->rewardDataMock = $this->createPartialMock(\Magento\Reward\Helper\Data::class, ['isEnabledOnFront']);
        $this->importerMock = $this->createMock(\Magento\Reward\Model\PaymentDataImporter::class);

        $this->subject = $objectManager->getObject(
            \Magento\Reward\Observer\ProcessOrderCreationData::class,
            ['rewardData' => $this->rewardDataMock, 'importer' => $this->importerMock]
        );
    }

    public function testPaymentDataImportIfRewardsDisabledOnFront()
    {
        $websiteId = 1;
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getStore', '__wakeup']);

        $orderCreateModel = $this->createMock(\Magento\Sales\Model\AdminOrder\Create::class);
        $orderCreateModel->expects($this->once())->method('getQuote')->will($this->returnValue($quoteMock));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrderCreateModel']);
        $eventMock->expects($this->once())->method('getOrderCreateModel')->will($this->returnValue($orderCreateModel));

        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->will($this->returnValue(false));

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $quoteMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testPaymentDataImportIfPaymentNotSet()
    {
        $websiteId = 1;
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getStore', '__wakeup']);

        $orderCreateModel = $this->createMock(\Magento\Sales\Model\AdminOrder\Create::class);
        $orderCreateModel->expects($this->once())->method('getQuote')->will($this->returnValue($quoteMock));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrderCreateModel', 'getRequest']);
        $eventMock->expects($this->once())->method('getOrderCreateModel')->will($this->returnValue($orderCreateModel));
        $eventMock->expects($this->once())->method('getRequest')->will($this->returnValue([]));

        $observerMock->expects($this->exactly(2))->method('getEvent')->will($this->returnValue($eventMock));

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->will($this->returnValue(true));

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $quoteMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testPaymentDataImportIfUseRewardsNotSet()
    {
        $websiteId = 1;
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getStore', '__wakeup']);

        $orderCreateModel = $this->createMock(\Magento\Sales\Model\AdminOrder\Create::class);
        $orderCreateModel->expects($this->once())->method('getQuote')->will($this->returnValue($quoteMock));

        $request = [
            'payment' => ['another_option' => true],
        ];

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrderCreateModel', 'getRequest']);
        $eventMock->expects($this->once())->method('getOrderCreateModel')->will($this->returnValue($orderCreateModel));
        $eventMock->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $observerMock->expects($this->exactly(2))->method('getEvent')->will($this->returnValue($eventMock));

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->will($this->returnValue(true));

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $quoteMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testPaymentDataImportSuccess()
    {
        $websiteId = 1;
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);

        $quoteMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            ['getStore', '__wakeup', 'getPayment']
        );

        $orderCreateModel = $this->createMock(\Magento\Sales\Model\AdminOrder\Create::class);
        $orderCreateModel->expects($this->once())->method('getQuote')->will($this->returnValue($quoteMock));

        $request = [
            'payment' => ['use_reward_points' => true],
        ];

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getOrderCreateModel', 'getRequest']);
        $eventMock->expects($this->once())->method('getOrderCreateModel')->will($this->returnValue($orderCreateModel));
        $eventMock->expects($this->once())->method('getRequest')->will($this->returnValue($request));

        $observerMock->expects($this->exactly(2))->method('getEvent')->will($this->returnValue($eventMock));

        $this->rewardDataMock->expects($this->once())
            ->method('isEnabledOnFront')
            ->with($websiteId)
            ->will($this->returnValue(true));

        $paymentMock = $this->createMock(\Magento\Quote\Model\Quote\Payment::class);

        $storeMock = $this->createMock(\Magento\Store\Model\Store::class);
        $storeMock->expects($this->once())->method('getWebsiteId')->will($this->returnValue($websiteId));
        $quoteMock->expects($this->once())->method('getStore')->will($this->returnValue($storeMock));
        $quoteMock->expects($this->once())->method('getPayment')->will($this->returnValue($paymentMock));

        $this->importerMock->expects($this->once())
            ->method('import')
            ->with($quoteMock, $paymentMock, true)
            ->will($this->returnSelf());

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
