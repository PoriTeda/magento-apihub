<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Model;

class RewardManagementTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Model\RewardManagement
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $quoteRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $rewardDataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $importerMock;

    protected function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->quoteRepositoryMock = $this->createMock(\Magento\Quote\Api\CartRepositoryInterface::class);
        $this->rewardDataMock = $this->createMock(\Magento\Reward\Helper\Data::class);
        $this->importerMock = $this->createMock(\Magento\Reward\Model\PaymentDataImporter::class);

        $this->model = $objectManager->getObject(
            \Magento\Reward\Model\RewardManagement::class,
            [
                'quoteRepository' => $this->quoteRepositoryMock,
                'rewardData' => $this->rewardDataMock,
                'importer' => $this->importerMock
            ]
        );
    }

    public function testSetRewards()
    {
        $cartId = 100;
        $this->rewardDataMock->expects($this->once())->method('isEnabledOnFront')->willReturn(true);

        $quoteMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote::class,
            ['__wakeup', 'getPayment', 'collectTotals']
        );
        $this->quoteRepositoryMock->expects($this->once())->method('getActive')->with($cartId)->willReturn($quoteMock);
        $this->quoteRepositoryMock->expects($this->once())->method('save')->with($quoteMock);

        $paymentMock = $this->createMock(\Magento\Quote\Model\Quote\Payment::class);

        $quoteMock->expects($this->once())->method('getPayment')->willReturn($paymentMock);
        $quoteMock->expects($this->once())->method('collectTotals')->willReturnSelf();

        $this->importerMock->expects($this->once())
            ->method('import')
            ->with($quoteMock, $paymentMock, true)
            ->willReturnSelf();

        $this->assertTrue($this->model->set($cartId));
    }

    public function testSetRewardsIfDisabledOnFront()
    {
        $this->rewardDataMock->expects($this->once())->method('isEnabledOnFront')->willReturn(false);
        $this->assertFalse($this->model->set(1));
    }
}
