<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Controller\Payment;

use Magento\Checkout\Model\Session;
use Magento\Eway\Controller\Payment\Complete;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Session\SessionManager;
use Magento\Framework\View\Layout\ProcessorInterface;
use Magento\Framework\View\Result\LayoutFactory;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\PaymentFailuresInterface;
use Psr\Log\LoggerInterface;

/**
 * Class AcceptTest
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @see \Magento\Eway\Controller\Payment\Complete
 */
class CompleteTest extends \PHPUnit\Framework\TestCase
{
    const ORDER_ID = 10;

    /**
     * @var Complete
     */
    private $controller;

    /**
     * @var CommandPoolInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $commandPoolMock;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loggerMock;

    /**
     * @var LayoutFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $layoutFactoryMock;

    /**
     * @var Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutSessionMock;

    /**
     * @var PaymentDataObjectFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDataObjectFactoryMock;

    /**
     * @var OrderRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderRepositoryMock;

    /**
     * @var SessionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $sessionManagerMock;

    /**
     * @var RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    /**
     * @var ProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $processorMock;

    /**
     * @var PaymentDataObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDataObjectMock;

    /**
     * @var PaymentFailuresInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentFailuresMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $contextMock = $this->getMockBuilder(\Magento\Framework\App\Action\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();
        $this->commandPoolMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Command\CommandPoolInterface::class)
            ->getMockForAbstractClass();
        $this->loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->getMockForAbstractClass();
        $this->layoutFactoryMock = $this->getMockBuilder(\Magento\Framework\View\Result\LayoutFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->checkoutSessionMock = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentDataObjectFactoryMock = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectFactory::class
        )->disableOriginalConstructor()
            ->getMock();
        $this->orderRepositoryMock = $this->getMockBuilder(\Magento\Sales\Api\OrderRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->sessionManagerMock = $this->getMockBuilder(\Magento\Framework\Session\SessionManager::class)
            ->setMethods(['getAccessCode'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->processorMock = $this->getMockBuilder(\Magento\Framework\View\Layout\ProcessorInterface::class)
            ->getMockForAbstractClass();
        $this->paymentFailuresMock = $this->getMockBuilder(PaymentFailuresInterface::class)
            ->setMethods(['handle'])
            ->getMock();
        $contextMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $this->prepareLayout();
        $this->prepareData();

        $this->controller = new Complete(
            $contextMock,
            $this->commandPoolMock,
            $this->loggerMock,
            $this->layoutFactoryMock,
            $this->checkoutSessionMock,
            $this->paymentDataObjectFactoryMock,
            $this->sessionManagerMock,
            $this->paymentFailuresMock
        );
    }

    private function prepareLayout()
    {
        $resultLayoutMock = $this->getMockBuilder(\Magento\Framework\View\Result\Layout::class)
            ->disableOriginalConstructor()
            ->getMock();
        $layoutMock = $this->getMockBuilder(\Magento\Framework\View\Layout::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->layoutFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($resultLayoutMock);

        $resultLayoutMock->expects($this->once())
            ->method('addDefaultHandle');
        $resultLayoutMock->expects($this->once())
            ->method('getLayout')
            ->willReturn($layoutMock);

        $layoutMock->expects($this->once())
            ->method('getUpdate')
            ->willReturn($this->processorMock);
    }

    private function prepareData()
    {
        $orderMock = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderInterface::class)
            ->getMockForAbstractClass();
        $orderPaymentMock = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->getMockForAbstractClass();
        $this->paymentDataObjectMock = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
        )->getMockForAbstractClass();

        $this->checkoutSessionMock->expects($this->once())
            ->method('getLastRealOrder')
            ->willReturn($orderMock);

        $orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($orderPaymentMock);
        $orderMock->method('getQuoteId')
            ->willReturn(1);
        $this->paymentDataObjectFactoryMock->expects($this->once())
            ->method('create')
            ->with($orderPaymentMock)
            ->willReturn($this->paymentDataObjectMock);
    }

    public function testExecute()
    {
        $commandMock = $this->getMockBuilder(\Magento\Payment\Gateway\CommandInterface::class)
            ->getMockForAbstractClass();

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn('test-params');

        $this->sessionManagerMock->expects($this->once())
            ->method('getAccessCode')
            ->willReturn('access_code');

        $this->commandPoolMock->expects($this->once())
            ->method('get')
            ->with('complete')
            ->willReturn($commandMock);

        $commandMock->expects($this->once())
            ->method('execute')
            ->with(
                [
                    'payment' => $this->paymentDataObjectMock,
                    'access_code' => 'access_code',
                    'request' => 'test-params',
                ]
            );

        $this->processorMock->expects($this->once())
            ->method('load')
            ->with(['response_success']);

        $this->assertInstanceOf(\Magento\Framework\View\Result\Layout::class, $this->controller->execute());
    }

    public function testExecuteException()
    {
        $commandMock = $this->getMockBuilder(\Magento\Payment\Gateway\CommandInterface::class)
            ->getMockForAbstractClass();

        $this->requestMock->expects($this->once())
            ->method('getParams')
            ->willReturn('test-params');

        $this->sessionManagerMock->expects($this->once())
            ->method('getAccessCode')
            ->willReturn('access_code');

        $this->commandPoolMock->expects($this->once())
            ->method('get')
            ->with('complete')
            ->willReturn($commandMock);

        $commandMock->expects($this->once())
            ->method('execute')
            ->with(
                [
                    'payment' => $this->paymentDataObjectMock,
                    'access_code' => 'access_code',
                    'request' => 'test-params',
                ]
            )->willThrowException(new \Exception());

        $this->loggerMock->expects($this->once())
            ->method('critical')
            ->with($this->isInstanceOf('\Exception'));

        $this->paymentFailuresMock->expects($this->once())
            ->method('handle')
            ->with(1);

        $this->processorMock->expects($this->once())
            ->method('load')
            ->with(['response_failure']);

        $this->controller->execute();
    }
}
