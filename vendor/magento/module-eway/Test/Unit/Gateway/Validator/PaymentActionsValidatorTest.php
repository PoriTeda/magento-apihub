<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Validator;

use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Eway\Gateway\Validator\PaymentActionsValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

/**
 * Class PaymentActionsValidatorrTest
 *
 * @see \Magento\Eway\Gateway\Validator\TransactionVoidValidator
 */
class PaymentActionsValidatorTest extends \PHPUnit\Framework\TestCase
{
    const RESPONSE_CODE = 'test-response-code';

    const TRANSACTION_ID = 'test-transaction-test';

    /**
     * @var PaymentActionsValidator
     */
    private $validator;

    /**
     * @var ResultInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultInterfaceFactoryMock;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->resultInterfaceFactoryMock = $this->getMockBuilder(
            \Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->validator = new PaymentActionsValidator($this->resultInterfaceFactoryMock);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Response does not exist
     */
    public function testValidateReadResponseException()
    {
        $validationSubject = [
            'response' => null
        ];

        $this->validator->validate($validationSubject);
    }

    /**
     * Run test for validate method
     *
     * @param array $validationSubject
     * @param $isValid
     * @param array $fails
     * @return void
     *
     * @dataProvider dataProviderTestValidate
     */
    public function testValidate(array $validationSubject, $isValid, array $fails)
    {
        /** @var ResultInterface|\PHPUnit_Framework_MockObject_MockObject $resultMock */
        $resultMock = $this->getMockBuilder(\Magento\Payment\Gateway\Validator\ResultInterface::class)
            ->getMockForAbstractClass();

        $this->resultInterfaceFactoryMock->expects($this->once())
            ->method('create')
            ->with(['isValid' => $isValid, 'failsDescription' => $fails, 'errorCodes' => []])
            ->willReturn($resultMock);

        $actualMock = $this->validator->validate($validationSubject);

        $this->assertEquals($resultMock, $actualMock);
    }

    /**
     * @return array
     */
    public function dataProviderTestValidate()
    {
        return [
            'success' => [
                'validationSubject' => [
                    'response' => [
                        'Errors' => null,
                        'TransactionStatus' => true,
                        'TransactionID' => self::TRANSACTION_ID,
                        'ResponseCode' => self::RESPONSE_CODE,
                        'ResponseMessage' => self::RESPONSE_CODE,
                    ],
                ],
                'isValid' => true,
                'fails' => []
            ],
            'fail-errors' => [
                'validationSubject' => [
                    'response' => [
                        'Errors' => true,
                        'TransactionStatus' => true,
                        'TransactionID' => self::TRANSACTION_ID,
                        'ResponseCode' => self::RESPONSE_CODE,
                        'ResponseMessage' => self::RESPONSE_CODE,
                    ],
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
            'fail-transaction-id' => [
                'validationSubject' => [
                    'response' => [
                        'Errors' => null,
                        'TransactionStatus' => true,
                        'ResponseCode' => self::RESPONSE_CODE,
                        'ResponseMessage' => self::RESPONSE_CODE,
                    ],
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
            'fail-data' => [
                'validationSubject' => [
                    'response' => [],
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
        ];
    }
}
