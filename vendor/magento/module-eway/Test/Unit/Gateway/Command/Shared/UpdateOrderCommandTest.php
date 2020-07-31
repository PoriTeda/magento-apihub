<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Command\Shared;

use Magento\Payment\Gateway\ConfigInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Eway\Gateway\Command\Shared\UpdateOrderCommand;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;

/**
 * Class UpdateOrderCommandTest
 *
 * @see \Magento\Eway\Gateway\Command\Shared\UpdateOrderCommand
 */
class UpdateOrderCommandTest extends \PHPUnit\Framework\TestCase
{
    const TOTAL = 100;

    /**
     * @var UpdateOrderCommand
     */
    private $updateOrderCommand;

    /**
     * @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    /**
     * @var OrderRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDoMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->configMock = $this->getMockBuilder(\Magento\Payment\Gateway\ConfigInterface::class)
            ->getMockForAbstractClass();
        $this->orderRepositoryMock = $this->getMockBuilder(\Magento\Sales\Api\OrderRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->paymentDoMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();

        $this->updateOrderCommand = new UpdateOrderCommand(
            $this->configMock,
            $this->orderRepositoryMock
        );
    }

    /**
     * Run test for execute method (capture)
     *
     * @return void
     */
    public function testExecuteCapture()
    {
        $paymentMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDoMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $this->configMock->expects($this->once())
            ->method('getValue')
            ->with('payment_action')
            ->willReturn(AbstractMethod::ACTION_AUTHORIZE_CAPTURE);

        $paymentMock->expects($this->once())
            ->method('capture');
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);

        $this->orderRepositoryMock->expects($this->once())
            ->method('save')
            ->with($orderMock);

        $this->updateOrderCommand->execute(['payment' => $this->paymentDoMock]);
    }

    /**
     * Run test for execute method (authorize)
     *
     * @return void
     */
    public function testExecuteAuthorize()
    {
        $paymentMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $orderAdapterMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();

        $this->paymentDoMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);
        $this->paymentDoMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderAdapterMock);

        $orderAdapterMock->expects($this->once())
            ->method('getGrandTotalAmount')
            ->willReturn(self::TOTAL);

        $this->configMock->expects($this->once())
            ->method('getValue')
            ->with('payment_action')
            ->willReturn(AbstractMethod::ACTION_AUTHORIZE);

        $paymentMock->expects($this->once())
            ->method('authorize')
            ->with(false, self::TOTAL);
        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);

        $this->orderRepositoryMock->expects($this->once())
            ->method('save')
            ->with($orderMock);

        $this->updateOrderCommand->execute(['payment' => $this->paymentDoMock]);
    }
}
