<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Controller\Payment;

use Magento\Eway\Controller\Payment\GetAccessCode;
use Magento\Eway\Gateway\Validator\AbstractResponseValidator;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\Exception;
use Magento\Sales\Api\PaymentFailuresInterface;

/**
 * Class GetAccessCodeTest
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetAccessCodeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Mock data order id
     */
    const ORDER_ID = '1';

    /**
     * Mock data total due
     */
    const TOTAL_DUE = 10.02;

    /**
     * Mock data access code
     */
    const ACCESS_CODE = 'access_code';

    /**
     * @var GetAccessCode
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
     * @var \Magento\Framework\Session\SessionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionManager;

    /**
     * @var \Magento\Framework\Controller\ResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resultFactory;

    /**
     * @var PaymentFailuresInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentFailures;

    /**
     * @inheritdoc
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

        $this->sessionManager = $this->getMockBuilder(\Magento\Framework\Session\SessionManager::class)
            ->setMethods(['setAccessCode'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultFactory = $this->getMockBuilder(\Magento\Framework\Controller\ResultFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentFailures = $this->getMockBuilder(PaymentFailuresInterface::class)
            ->setMethods(['handle'])
            ->getMock();

        $context->expects($this->once())
            ->method('getResultFactory')
            ->willReturn($this->resultFactory);

        $this->controller = new GetAccessCode(
            $context,
            $this->commandPool,
            $this->logger,
            $this->orderRepository,
            $this->paymentDataObjectFactory,
            $this->checkoutSession,
            $this->sessionManager,
            $this->paymentFailures
        );
    }

    /**
     * @return void
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
     * @return void
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
        $order->method('getQuoteId')
            ->willReturn(1);
        $controllerResult->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(Exception::HTTP_BAD_REQUEST);
        $controllerResult->expects($this->once())
            ->method('setData')
            ->with(['message' => __('Sorry, but something went wrong')]);
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->isInstanceOf('\Exception'));
        $this->paymentFailures->expects($this->once())
            ->method('handle')
            ->with(1);

        $this->controller->execute();
    }

    /**
     * @return void
     */
    public function testExecuteReadAccessCodeError()
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
            ->with(self::ORDER_ID)
            ->willReturn($order);
        $order->expects($this->once())
            ->method('getPayment')
            ->willReturn($payment);
        $this->paymentDataObjectFactory->expects($this->once())
            ->method('create')
            ->with($payment)
            ->willReturn($paymentDO);
        $order->expects($this->once())
            ->method('getTotalDue')
            ->willReturn(self::TOTAL_DUE);
        $this->commandPool->expects($this->once())
            ->method('get')
            ->with('get_access_code')
            ->willReturn($commandMock);
        $commandMock->expects($this->once())
            ->method('execute')
            ->with(
                [
                    'payment' => $paymentDO,
                    'amount' => self::TOTAL_DUE,
                ]
            )
            ->willReturn($commandResult);
        $commandResult->expects($this->once())
            ->method('get')
            ->willReturn(['AccessCode' => null]);
        $controllerResult->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(Exception::HTTP_BAD_REQUEST);
        $controllerResult->expects($this->once())
            ->method('setData')
            ->with(['message' => __('Sorry, but something went wrong')]);
        $this->logger->expects($this->once())
            ->method('critical')
            ->with($this->isInstanceOf('\Exception'));
        $this->paymentFailures->expects($this->once())
            ->method('handle');

        $this->controller->execute();
    }

    /**
     * @return void
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
        $order->expects($this->once())
            ->method('getTotalDue')
            ->willReturn(self::TOTAL_DUE);
        $this->commandPool->expects($this->once())
            ->method('get')
            ->with('get_access_code')
            ->willReturn($commandMock);
        $commandMock->expects($this->once())
            ->method('execute')
            ->with(
                [
                    'payment' => $paymentDO,
                    'amount' => self::TOTAL_DUE,
                ]
            )
            ->willReturn($commandResult);
        $commandResult->expects($this->once())
            ->method('get')
            ->willReturn([AbstractResponseValidator::ACCESS_CODE => self::ACCESS_CODE]);
        $this->sessionManager->expects($this->once())
            ->method('setAccessCode')
            ->with(self::ACCESS_CODE);
        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($controllerResult);
        $controllerResult->expects($this->once())
            ->method('setData')
            ->with(['access_code' => self::ACCESS_CODE]);

        $this->assertSame($controllerResult, $this->controller->execute());
    }
}
