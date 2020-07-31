<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Observer;

class PaymentDataImportTest extends \PHPUnit\Framework\TestCase
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
     * @var \Magento\Reward\Observer\PaymentDataImport
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->rewardDataMock = $this->createMock(\Magento\Reward\Helper\Data::class);
        $this->importerMock = $this->createMock(\Magento\Reward\Model\PaymentDataImporter::class);

        $this->subject = $objectManager->getObject(
            \Magento\Reward\Observer\PaymentDataImport::class,
            ['rewardData' => $this->rewardDataMock, 'importer' => $this->importerMock]
        );
    }

    public function testPaymentDataImportIfRewardsDisabledOnFront()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->rewardDataMock->expects($this->once())->method('isEnabledOnFront')->will($this->returnValue(false));
        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testPaymentDataImportSuccess()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->rewardDataMock->expects($this->once())->method('isEnabledOnFront')->will($this->returnValue(true));

        $inputMock =
            $this->createPartialMock(\Magento\Framework\DataObject::class, ['getAdditionalData', '__wakeup']);
        $inputMock->expects($this->once())
            ->method('getAdditionalData')
            ->will($this->returnValue(['use_reward_points' => true]));
        $quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getIsMultiShipping']);
        $quoteMock->expects($this->once())->method('getIsMultiShipping')->willReturn(true);
        $paymentMock = $this->createPartialMock(\Magento\Sales\Model\Order\Payment::class, ['getQuote', '__wakeup']);
        $paymentMock->expects($this->once())->method('getQuote')->will($this->returnValue($quoteMock));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getRule', 'getInput', 'getPayment']);
        $eventMock->expects($this->once())->method('getInput')->will($this->returnValue($inputMock));
        $eventMock->expects($this->once())->method('getPayment')->will($this->returnValue($paymentMock));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $this->importerMock->expects($this->once())
            ->method('import')
            ->with($quoteMock, $inputMock, true)
            ->will($this->returnSelf());

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testPaymentDataImportOnePageCheckout()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->rewardDataMock->expects($this->once())->method('isEnabledOnFront')->will($this->returnValue(true));

        $inputMock =
            $this->createPartialMock(
                \Magento\Framework\DataObject::class,
                ['getAdditionalData', '__wakeup', 'getUseRewardPoints']
            );
        $inputMock->expects($this->never())->method('getUseRewardPoints');
        $quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getIsMultiShipping']);
        $quoteMock->expects($this->once())->method('getIsMultiShipping')->willReturn(false);
        $paymentMock = $this->createPartialMock(\Magento\Sales\Model\Order\Payment::class, ['getQuote', '__wakeup']);
        $paymentMock->expects($this->once())->method('getQuote')->will($this->returnValue($quoteMock));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getRule', 'getInput', 'getPayment']);
        $eventMock->expects($this->once())->method('getInput')->will($this->returnValue($inputMock));
        $eventMock->expects($this->once())->method('getPayment')->will($this->returnValue($paymentMock));
        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $this->importerMock->expects($this->never())->method('import');

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
