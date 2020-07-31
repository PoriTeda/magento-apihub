<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Observer;

class QuoteMergeAfterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Observer\QuoteMergeAfter
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->subject = $objectManager->getObject(\Magento\Reward\Observer\QuoteMergeAfter::class);
    }

    public function testSetFlagToResetRewardPoints()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $quoteMock = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['setUseRewardPoints', '__wakeup']);
        $quoteMock->expects($this->once())
            ->method('setUseRewardPoints')
            ->with(true)
            ->will($this->returnSelf());

        $sourceMock = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getUseRewardPoints']);
        $sourceMock->expects($this->exactly(2))->method('getUseRewardPoints')->will($this->returnValue(true));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getQuote', 'getSource']);
        $eventMock->expects($this->once())->method('getQuote')->will($this->returnValue($quoteMock));
        $eventMock->expects($this->once())->method('getSource')->will($this->returnValue($sourceMock));
        $observerMock->expects($this->exactly(2))->method('getEvent')->will($this->returnValue($eventMock));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }

    public function testSetFlagToResetRewardPointsIfRewardPointsIsNull()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);

        $sourceMock = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getUseRewardPoints']);
        $sourceMock->expects($this->once())->method('getUseRewardPoints')->will($this->returnValue(false));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getQuote', 'getSource']);
        $eventMock->expects($this->once())->method('getQuote')->will($this->returnValue($quoteMock));
        $eventMock->expects($this->once())->method('getSource')->will($this->returnValue($sourceMock));
        $observerMock->expects($this->exactly(2))->method('getEvent')->will($this->returnValue($eventMock));

        $this->assertEquals($this->subject, $this->subject->execute($observerMock));
    }
}
