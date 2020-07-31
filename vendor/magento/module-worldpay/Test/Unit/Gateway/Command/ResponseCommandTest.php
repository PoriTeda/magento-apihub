<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Gateway\Command;

use Magento\Sales\Model\Order;
use Magento\Worldpay\Gateway\Command\ResponseCommand;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Worldpay\Gateway\Request\HtmlRedirect\OrderDataBuilder;
use Magento\Worldpay\Gateway\Validator\DecisionValidator;

/**
 * Class ResponseCommandTest
 *
 * Test for class \Magento\Worldpay\Gateway\Command\ResponseCommand
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ResponseCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ResponseCommand
     */
    protected $responseCommand;

    /**
     * @var CommandPoolInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $commandPool;

    /**
     * @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $validator;

    /**
     * @var OrderRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderRepository;

    /**
     * @var PaymentDataObjectFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDataObjectFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->commandPool = $this->getMockBuilder(\Magento\Payment\Gateway\Command\CommandPoolInterface::class)
            ->getMockForAbstractClass();
        $this->validator = $this->getMockBuilder(\Magento\Payment\Gateway\Validator\ValidatorInterface::class)
            ->getMockForAbstractClass();
        $this->orderRepository = $this->getMockBuilder(\Magento\Sales\Api\OrderRepositoryInterface::class)
            ->getMockForAbstractClass();
        $this->logger = $this->getMockBuilder(\Magento\Payment\Model\Method\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDataObjectFactory = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectFactory::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseCommand = new ResponseCommand(
            $this->commandPool,
            $this->validator,
            $this->orderRepository,
            $this->paymentDataObjectFactory,
            $this->logger
        );
    }

    /**
     * Run test execute method
     */
    public function testExecute()
    {
        $response = [
            OrderDataBuilder::ORDER_ID => '1',
            'transStatus' => 'Y'
        ];
        $commandSubject = [
            'response' => $response
        ];

        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $commandMock = $this->getMockBuilder(\Magento\Payment\Gateway\CommandInterface::class)
            ->getMockForAbstractClass();
        $orderPaymentMock = $this->getMockBuilder(\Magento\Payment\Model\InfoInterface::class)
            ->getMockForAbstractClass();
        $resultMock = $this->getMockBuilder(
            \Magento\Payment\Gateway\Validator\ResultInterface::class
        )->getMockForAbstractClass();
        $orderMock = $this->getMockBuilder(\Magento\Sales\Api\Data\OrderInterface::class)
            ->getMockForAbstractClass();

        $this->logger->expects(static::once())
            ->method('debug')
            ->with($commandSubject);
        $commandMock->expects(static::once())
            ->method('execute')
            ->with(['response' => $response, 'payment' => $paymentDO]);
        $orderMock->expects(static::once())
            ->method('getPayment')
            ->willReturn($orderPaymentMock);

        $this->paymentDataObjectFactory->expects(static::once())
            ->method('create')
            ->with($orderPaymentMock)
            ->willReturn($paymentDO);

        $this->validator->expects(static::once())
            ->method('validate')
            ->with($commandSubject)
            ->willReturn($resultMock);
        $resultMock->expects(static::once())
            ->method('isValid')
            ->willReturn(true);

        $this->orderRepository->expects(static::once())
            ->method('get')
            ->with($response[OrderDataBuilder::ORDER_ID])
            ->willReturn($orderMock);

        $this->commandPool->expects(static::once())
            ->method('get')
            ->willReturn($commandMock);

        $this->responseCommand->execute($commandSubject);
    }
}
