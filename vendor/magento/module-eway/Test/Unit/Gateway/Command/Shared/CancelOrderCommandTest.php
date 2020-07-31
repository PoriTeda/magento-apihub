<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Command\Shared;

use Magento\Checkout\Model\Session;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Eway\Gateway\Command\Shared\CancelOrderCommand;
use Magento\Payment\Gateway\Command\Result\BoolResultFactory;

/**
 * Class CancelOrderCommandTest
 *
 * @see \Magento\Eway\Gateway\Command\Shared\CancelOrderCommand
 */
class CancelOrderCommandTest extends \PHPUnit\Framework\TestCase
{
    const ORDER_ID = 10;

    const INCREMENT_ID = '000000232';

    /**
     * @var CancelOrderCommand
     */
    private $cancelCommand;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var OrderManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderManagementMock;

    /**
     * @var BoolResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultFactoryMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->orderManagementMock = $this->getMockBuilder(\Magento\Sales\Api\OrderManagementInterface::class)
            ->getMockForAbstractClass();
        $this->checkoutSessionMock = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->setMethods(['setLastRealOrderId', 'restoreQuote'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resultFactoryMock = $this->getMockBuilder(
            \Magento\Payment\Gateway\Command\Result\BoolResultFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->cancelCommand = new CancelOrderCommand(
            $this->orderManagementMock,
            $this->checkoutSessionMock,
            $this->resultFactoryMock
        );
    }

    /**
     * Run test for execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $paymentDOMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $orderAdapterMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMockForAbstractClass();

        $paymentDOMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderAdapterMock);

        $orderAdapterMock->expects($this->once())
            ->method('getId')
            ->willReturn(self::ORDER_ID);

        $this->orderManagementMock->expects($this->once())
            ->method('cancel')
            ->with(self::ORDER_ID);

        $this->checkoutSessionMock->expects($this->once())
            ->method('restoreQuote')
            ->willReturn(true);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(['result' => true]);

        $this->cancelCommand->execute(['payment' => $paymentDOMock]);
    }
}
