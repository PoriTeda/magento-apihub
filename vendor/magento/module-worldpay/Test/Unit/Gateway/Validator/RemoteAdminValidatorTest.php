<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Gateway\Validator;

use Magento\Worldpay\Gateway\Validator\RemoteAdminValidator;
use Magento\Payment\Gateway\Validator\Result;

class RemoteAdminValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDO;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentInfo;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resultFactory;

    /**
     * @var RemoteAdminValidator
     */
    private $validator;

    protected function setUp()
    {
        $this->resultFactory = $this->getMockBuilder(
            \Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentDO = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
        )
            ->getMockForAbstractClass();
        $this->paymentInfo = $this->getMockBuilder(
            \Magento\Sales\Model\Order\Payment::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new RemoteAdminValidator($this->resultFactory);
    }

    public function testValidateEmptyResponse()
    {
        $this->paymentDO->expects(static::any())
            ->method('getPayment')
            ->willReturn($this->paymentInfo);
        $this->resultFactory->expects(static::once())
            ->method('create')
            ->with(
                [
                    'isValid' => false,
                    'failsDescription' => [__('Gateway processing error.')],
                    'errorCodes' => []
                ]
            )->willReturn(new Result(false, [__('Gateway processing error.')]));

        $result = $this->validator->validate(
            [
                'response' => [],
                'payment' => $this->paymentDO
            ]
        );

        static::assertFalse($result->isValid());
        static::assertEquals(
            [__('Gateway processing error.')],
            $result->getFailsDescription()
        );
    }

    public function testValidateWrongStatus()
    {
        $this->paymentDO->expects(static::any())
            ->method('getPayment')
            ->willReturn($this->paymentInfo);
        $this->paymentInfo->expects(static::once())
            ->method('getParentTransactionId')
            ->willReturn(101);
        $this->resultFactory->expects(static::once())
            ->method('create')
            ->with(
                [
                    'isValid' => false,
                    'failsDescription' => [__('Transaction was not placed.')],
                    'errorCodes' => []
                ]
            )->willReturn(new Result(false, [__('Transaction was not placed.')]));

        $result = $this->validator->validate(
            [
                'response' => ['A', '102'],
                'payment' => $this->paymentDO
            ]
        );

        static::assertFalse($result->isValid());
        static::assertEquals(
            [__('Transaction was not placed.')],
            $result->getFailsDescription()
        );
    }

    public function testValidateSuccess()
    {
        $this->paymentDO->expects(static::any())
            ->method('getPayment')
            ->willReturn($this->paymentInfo);
        $this->paymentInfo->expects(static::once())
            ->method('getParentTransactionId')
            ->willReturn(101);
        $this->resultFactory->expects(static::once())
            ->method('create')
            ->with(
                [
                    'isValid' => true,
                    'failsDescription' => [],
                    'errorCodes' => []
                ]
            )->willReturn(new Result(true, []));

        $result = $this->validator->validate(
            [
                'response' => ['A', '101'],
                'payment' => $this->paymentDO
            ]
        );

        static::assertTrue($result->isValid());
        static::assertEmpty($result->getFailsDescription());
    }
}
