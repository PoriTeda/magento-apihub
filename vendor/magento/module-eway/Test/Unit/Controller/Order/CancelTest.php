<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Controller\Order;

use Magento\Eway\Controller\Order\Cancel;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\Exception;

/**
 * Class CancelTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CancelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Mock data order id
     */
    const ORDER_ID = '1';

    /**
     * Mock data of cancel order command execution
     */
    const COMMAND_RESULT = true;

    /**
     * @var Cancel
     */
    protected $controller;

    /**
     * @var \Magento\Payment\Gateway\Command\CommandPoolInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $commandPool;

    /**
     * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $orderRepository;

    /**
     * @var \Magento\Payment\Gateway\Data\PaymentDataObjectFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentDataObjectFactory;

    /**
     * @var \Magento\Checkout\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\Controller\ResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultFactory;

    /**
     *
     */
    protected function setUp()
    {
        /** @var \Magento\Framework\App\Action\Context|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMockBuilder(\Magento\Framework\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->commandPool = $this->getMockBuilder(\Magento\Payment\Gateway\Command\CommandPoolInterface::class)
            ->getMockForAbstractClass();

        $this->logger = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->getMockForAbstractClass();

        $this->orderRepository = $this->getMockBuilder(\Magento\Sales\Api\OrderRepositoryInterface::class)
            ->getMockForAbstractClass();

        $this->paymentDataObjectFactory = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectFactory::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutSession = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactory = $this->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects($this->once())
            ->method('getResultFactory')
            ->willReturn($this->resultFactory);

        $this->controller = new Cancel(
            $context,
            $this->commandPool,
            $this->logger,
            $this->orderRepository,
            $this->paymentDataObjectFactory,
            $this->checkoutSession
        );
    }

    /**
     *
     */
    public function testExecuteInvalidOrderIdError()
    {
        $controllerResult = $this->getMockBuilder(\Magento\Framework\Controller\ResultInterface::class)
            ->setMethods(['setData'])
            ->getMockForAbstractClass();

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($controllerResult);
        $this->checkoutSession->expects($this->once())
            ->method('getData')
            ->with('last_order_id')
            ->willReturn('first');
        $controllerResult->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(Exception::HTTP_BAD_REQUEST);
        $controllerResult->expects($this->once())
            ->method('setData')
            ->with(['message' => __('Sorry, but something went wrong')]);

        $this->controller->execute();
    }

    /**
     *
     */
    public function testExecuteAssertOrderPaymentError()
    {
        $order = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderInterface::class)
            ->getMockForAbstractClass();
        $wrongPayment = $this->getMockBuilder(\Magento\Quote\Model\Quote\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $controllerResult = $this->getMockBuilder(\Magento\Framework\Controller\ResultInterface::class)
            ->setMethods(['setData'])
            ->getMockForAbstractClass();

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($controllerResult);
        $this->checkoutSession->expects($this->once())
            ->method('getData')
            ->with('last_order_id')
            ->willReturn(self::ORDER_ID);
        $this->orderRepository->expects($this->once())
            ->method('get')
            ->with((int) self::ORDER_ID)
            ->willReturn($order);
        $order->expects($this->once())
            ->method('getPayment')
            ->willReturn($wrongPayment);
        $controllerResult->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(Exception::HTTP_BAD_REQUEST);
        $controllerResult->expects($this->once())
            ->method('setData')
            ->with(['message' => __('Sorry, but something went wrong')]);
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->isInstanceOf('\Exception'));

        $this->controller->execute();
    }

    /**
     *
     */
    public function testExecute()
    {
        $order = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderInterface::class)
            ->getMockForAbstractClass();
        $payment = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $commandMock = $this->getMockBuilder(\Magento\Payment\Gateway\CommandInterface::class)
            ->getMockForAbstractClass();
        $commandResult = $this->getMockBuilder(\Magento\Payment\Gateway\Command\ResultInterface::class)
            ->getMockForAbstractClass();
        $controllerResult = $this->getMockBuilder(\Magento\Framework\Controller\ResultInterface::class)
            ->setMethods(['setData'])
            ->getMockForAbstractClass();

        $this->checkoutSession->expects($this->once())
            ->method('getData')
            ->with('last_order_id')
            ->willReturn(self::ORDER_ID);
        $this->orderRepository->expects($this->once())
            ->method('get')
            ->with(self::ORDER_ID)
            ->willReturn($order);
        $order->expects($this->once())
            ->method('getPayment')
            ->willReturn($payment);
        $this->paymentDataObjectFactory->expects($this->once())
            ->method('create')
            ->with($payment)
            ->willReturn($paymentDO);
        $this->commandPool->expects($this->once())
            ->method('get')
            ->with('cancel_order')
            ->willReturn($commandMock);
        $commandMock->expects($this->once())
            ->method('execute')
            ->with(
                [
                    'payment' => $paymentDO
                ]
            )
            ->willReturn($commandResult);
        $commandResult->expects($this->once())
            ->method('get')
            ->willReturn(self::COMMAND_RESULT);
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($controllerResult);
        $controllerResult->expects($this->once())
            ->method('setData')
            ->with(self::COMMAND_RESULT);

        $this->assertSame($controllerResult, $this->controller->execute());
    }
}
