<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Validator\SilentOrder;

use Magento\Cybersource\Gateway\Validator\SilentOrder\SignValidator;
use Magento\Payment\Gateway\ConfigInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Validator\Result;

class SignValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resultFactory;

    /**
     * @var SignValidator
     */
    private $validator;

    /**
     * @var ConfigInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var PaymentDataObjectInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDO;

    /**
     * @var OrderAdapterInterface | \PHPUnit_Framework_MockObject_MockObject
     */
    private $orderAdapter;

    protected function setUp()
    {
        $this->resultFactory = $this->getMockBuilder(
            \Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class
        )
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->config = $this->getMockBuilder(\Magento\Payment\Gateway\ConfigInterface::class)
            ->getMockForAbstractClass();
        $this->paymentDO = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
        )->getMockForAbstractClass();
        $this->orderAdapter = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\OrderAdapterInterface::class
        )->getMockForAbstractClass();

        $this->validator = new SignValidator($this->resultFactory, $this->config);
    }

    public function testValidateNoFieldsToValidate()
    {
        $validationSubject = [
            'payment' => $this->paymentDO,
            'response' => []
        ];

        $this->resultFactory->expects(static::once())
            ->method('create')
            ->with(['isValid' => false, 'failsDescription' => [__('Gateway validation error')],'errorCodes' => []])
            ->willReturn(new Result(false, [__('Gateway validation error')]));

        $result = $this->validator->validate($validationSubject);
        static::assertFalse($result->isValid());
    }

    public function testValidateSignParseException()
    {
        $validationSubject = [
            'payment' => $this->paymentDO,
            'response' => [
                SignValidator::SIGNED_FIELD_NAMES => 'existingField,nonExistingField',
                SignValidator::SIGNATURE => 'bla',
                'existingField' => 'value'
            ]
        ];

        $this->resultFactory->expects(static::once())
            ->method('create')
            ->with(['isValid' => false, 'failsDescription' => [__('Gateway validation error')], 'errorCodes' => []])
            ->willReturn(new Result(false, [__('Gateway validation error')]));

        $result = $this->validator->validate($validationSubject);
        static::assertFalse($result->isValid());
    }

    public function testValidate()
    {
        $validationSubject = [
            'payment' => $this->paymentDO,
            'response' => [
                SignValidator::SIGNED_FIELD_NAMES => 'field',
                SignValidator::SIGNATURE => 'DtfdsHBHfTDNtuphHKwwRlSklaBhY5kyiyaFWVp2AsA=',
                'field' => 'value'
            ]
        ];

        $this->paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($this->orderAdapter);
        $this->orderAdapter->expects(static::once())
            ->method('getStoreId')
            ->willReturn(1);
        $this->config->expects(static::once())
            ->method('getValue')
            ->with('secret_key', 1)
            ->willReturn('KEY');

        $this->resultFactory->expects(static::once())
            ->method('create')
            ->with(['isValid' => true, 'failsDescription' => [],'errorCodes' => []])
            ->willReturn(new Result(true, []));

        $result = $this->validator->validate($validationSubject);
        static::assertTrue($result->isValid());
    }
}
