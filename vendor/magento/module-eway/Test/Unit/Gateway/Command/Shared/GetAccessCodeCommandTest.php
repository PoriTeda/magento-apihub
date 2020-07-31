<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Command\Shared;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Payment\Gateway\Validator\ValidatorInterface;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Eway\Gateway\Command\Shared\GetAccessCodeCommand;
use Magento\Payment\Gateway\Command\Result\ArrayResultFactory;
use Magento\Eway\Gateway\Validator\Shared\AccessCodeValidator;

/**
 * Class GetAccessCodeCommandTest
 *
 * @see \Magento\Eway\Gateway\Command\Shared\GetAccessCodeCommand
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetAccessCodeCommandTest extends \PHPUnit\Framework\TestCase
{
    const ACCESS_CODE = 'test-access-code';

    /**
     * @var GetAccessCodeCommand
     */
    private $getAccessCodeCommand;

    /**
     * @var BuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestBuilderMock;

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
     * @var ArrayResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultFactoryMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->requestBuilderMock = $this->getMockBuilder(\Magento\Payment\Gateway\Request\BuilderInterface::class)
            ->getMockForAbstractClass();
        $this->transferFactoryMock = $this->getMockBuilder(
            \Magento\Payment\Gateway\Http\TransferFactoryInterface::class
        )
            ->getMockForAbstractClass();
        $this->clientMock = $this->getMockBuilder(\Magento\Payment\Gateway\Http\ClientInterface::class)
            ->getMockForAbstractClass();
        $this->validatorMock = $this->getMockBuilder(\Magento\Payment\Gateway\Validator\ValidatorInterface::class)
            ->getMockForAbstractClass();
        $this->resultFactoryMock = $this->getMockBuilder(
            \Magento\Payment\Gateway\Command\Result\ArrayResultFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->getAccessCodeCommand = new GetAccessCodeCommand(
            $this->requestBuilderMock,
            $this->transferFactoryMock,
            $this->clientMock,
            $this->resultFactoryMock,
            $this->validatorMock
        );
    }

    /**
     * Run test for execute method
     *
     * @return void
     */
    public function testExecute()
    {
        $commandResultMock = $this->getMockBuilder(\Magento\Payment\Gateway\Command\ResultInterface::class)
            ->getMockForAbstractClass();

        list($resultMock, $commandSubject) = $this->getTestData();

        /** @var \PHPUnit_Framework_MockObject_MockObject $resultMock */
        $resultMock->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(
                [
                    'array' => [
                        AccessCodeValidator::ACCESS_CODE => self::ACCESS_CODE
                    ]
                ]
            )->willReturn($commandResultMock);

        $this->assertEquals($commandResultMock, $this->getAccessCodeCommand->execute($commandSubject));
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

        $this->resultFactoryMock->expects($this->never())
            ->method('create');

        $this->getAccessCodeCommand->execute($commandSubject);
    }

    /**
     * @return array
     */
    private function getTestData()
    {
        $commandSubject = ['test-command-subject'];
        $request = ['test-request-data'];
        $response = [AccessCodeValidator::ACCESS_CODE => self::ACCESS_CODE];

        $transferOMock = $this->getMockBuilder(\Magento\Payment\Gateway\Http\TransferInterface::class)
            ->getMockForAbstractClass();
        $resultMock = $this->getMockBuilder(\Magento\Payment\Gateway\Validator\ResultInterface::class)
            ->getMockForAbstractClass();

        $this->requestBuilderMock->expects($this->once())
            ->method('build')
            ->with($commandSubject)
            ->willReturn($request);

        $this->transferFactoryMock->expects($this->once())
            ->method('create')
            ->with($request)
            ->willReturn($transferOMock);

        $this->clientMock->expects($this->once())
            ->method('placeRequest')
            ->with($transferOMock)
            ->willReturn($response);

        $this->validatorMock->expects($this->once())
            ->method('validate')
            ->with(array_merge($commandSubject, ['response' => $response]))
            ->willReturn($resultMock);

        return [$resultMock, $commandSubject];
    }
}
