<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Command\Shared;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Eway\Gateway\Command\Shared\UpdateDetailsCommand;
use Magento\Eway\Gateway\Validator\Shared\AccessCodeValidator;

/**
 * Class UpdateDetailsCommandTest
 *
 * @see \Magento\Eway\Gateway\Command\Shared\UpdateDetailsCommand
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class UpdateDetailsCommandTest extends \PHPUnit\Framework\TestCase
{
    const ACCESS_CODE = 'test-access-code';

    const RESPONSE = 'test-response';

    const AMOUNT = 100;

    /**
     * @var UpdateDetailsCommand
     */
    private $updateDetailsCommand;

    /**
     * @var TransferFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $transferFactoryMock;

    /**
     * @var ClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $clientMock;

    /**
     * @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $validatorMock;

    /**
     * @var HandlerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $handlerMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->transferFactoryMock = $this->getMockBuilder(
            \Magento\Payment\Gateway\Http\TransferFactoryInterface::class
        )
            ->getMockForAbstractClass();
        $this->clientMock = $this->getMockBuilder(\Magento\Payment\Gateway\Http\ClientInterface::class)
            ->getMockForAbstractClass();
        $this->validatorMock = $this->getMockBuilder(\Magento\Payment\Gateway\Validator\ValidatorInterface::class)
            ->getMockForAbstractClass();
        $this->handlerMock = $this->getMockBuilder(\Magento\Payment\Gateway\Response\HandlerInterface::class)
            ->getMockForAbstractClass();

        $this->updateDetailsCommand = new UpdateDetailsCommand(
            $this->transferFactoryMock,
            $this->clientMock,
            $this->validatorMock,
            $this->handlerMock
        );
    }

    /**
     * Run test for execute method
     *
     * @return void
     */
    public function testExecute()
    {
        list($resultMock, $commandSubject) = $this->getTestData();

        /** @var \PHPUnit_Framework_MockObject_MockObject $resultMock */
        $resultMock->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->handlerMock->expects($this->once())
            ->method('handle')
            ->with($commandSubject, [self::RESPONSE]);

        $this->updateDetailsCommand->execute($commandSubject);
    }

    /**
     * Run test for execute method (Exception)
     *
     * @return void
     *
     * @expectedException \Magento\Payment\Gateway\Command\CommandException
     * @expectedExceptionMessageRegExp /message-1\nmessage-2/
     */
    public function testExecuteException()
    {
        list($resultMock, $commandSubject) = $this->getTestData();

        /** @var \PHPUnit_Framework_MockObject_MockObject $resultMock */
        $resultMock->expects($this->once())
            ->method('isValid')
            ->willReturn(false);
        $resultMock->expects($this->once())
            ->method('getFailsDescription')
            ->willReturn(['message-1', 'message-2']);

        $this->handlerMock->expects($this->never())
            ->method('handle');

        $this->updateDetailsCommand->execute($commandSubject);
    }

    /**
     * @return array
     */
    private function getTestData()
    {
        $paymentDoMock = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();
        $paymentMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $transferOMock = $this->getMockBuilder(\Magento\Payment\Gateway\Http\TransferInterface::class)
            ->getMockForAbstractClass();
        $orderMock = $this->getMockBuilder(\Magento\Sales\Model\Order::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resultMock = $this->getMockBuilder(\Magento\Payment\Gateway\Validator\ResultInterface::class)
            ->getMockForAbstractClass();

        $commandSubject = [
            'access_code' => self::ACCESS_CODE,
            'payment' => $paymentDoMock
        ];
        $response = [self::RESPONSE];

        $paymentDoMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($paymentMock);

        $paymentMock->expects($this->once())
            ->method('getOrder')
            ->willReturn($orderMock);

        $this->transferFactoryMock->expects($this->once())
            ->method('create')
            ->with([AccessCodeValidator::ACCESS_CODE => self::ACCESS_CODE])
            ->willReturn($transferOMock);

        $this->clientMock->expects($this->once())
            ->method('placeRequest')
            ->with($transferOMock)
            ->willReturn($response);

        $orderMock->expects($this->once())
            ->method('getTotalDue')
            ->willReturn(self::AMOUNT);

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->with(
                array_merge(
                    $commandSubject,
                    [
                        'response' => $response,
                        'amount' => self::AMOUNT
                    ]
                )
            )->willReturn($resultMock);

        return [$resultMock, $commandSubject];
    }
}
