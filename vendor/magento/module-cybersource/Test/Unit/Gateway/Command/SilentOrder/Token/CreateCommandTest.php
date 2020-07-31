<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Command\SilentOrder\Token;

use Magento\Cybersource\Gateway\Command\SilentOrder\Token\CreateCommand;

/**
 * Class CreateCommandTest
 * @package Magento\Cybersource\Gateway\Command\SilentOrder\Token
 */
class CreateCommandTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CreateCommand
     */
    protected $command;

    /**
     * @var \Magento\Payment\Gateway\Request\BuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $builder;

    /**
     * @var \Magento\Payment\Gateway\Command\Result\ArrayResultFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $arrayResultFactory;

    /**
     * @var \Magento\Payment\Model\Method\Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    protected function setUp()
    {
        $this->builder = $this->getMockBuilder(\Magento\Payment\Gateway\Request\BuilderInterface::class)
            ->getMockForAbstractClass();
        $this->arrayResultFactory = $this->getMockBuilder(
            \Magento\Payment\Gateway\Command\Result\ArrayResultFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(\Magento\Payment\Model\Method\Logger::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->command = new CreateCommand($this->builder, $this->arrayResultFactory, $this->logger);
    }

    public function testExecute()
    {
        $commandSubject['amount'] = 20;
        $commandSubject['payment'] = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
        )
            ->getMockForAbstractClass();
        $arrayResult = $this->getMockBuilder(\Magento\Payment\Gateway\Command\Result\ArrayResult::class)
            ->disableOriginalConstructor()
            ->getMock();

        $buildResult = ['somefield' => 'somevalue'];
        $this->builder->expects($this->once())
            ->method('build')
            ->with($commandSubject)
            ->willReturn($buildResult);
        $this->logger->expects($this->once())
            ->method('debug')
            ->with(['payment_token_request' => $buildResult]);
        $this->arrayResultFactory->expects($this->once())
            ->method('create')
            ->with(['array' => $buildResult])
            ->willReturn($arrayResult);

        $this->assertInstanceOf(
            \Magento\Payment\Gateway\Command\Result\ArrayResult::class,
            $this->command->execute($commandSubject)
        );
    }
}
