<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Validator;

use Magento\Eway\Gateway\Validator\RefundValidator;
use Magento\Payment\Gateway\Command\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class RefundValidatorTest extends \PHPUnit\Framework\TestCase
{
    const AMOUNT_DATA = 10;

    /**
     * @var RefundValidator
     */
    private $validator;

    /**
     * @var ResultInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $resultInterfaceFactory;

    protected function setUp()
    {
        $this->resultInterfaceFactory = $this->getMockBuilder(
            \Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class
        )->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->validator = new RefundValidator($this->resultInterfaceFactory);
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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Amount should be provided
     */
    public function testValidateReadAmountException()
    {
        $validationSubject = [
            'response' => ['data'],
            'amount' => null
        ];

        $this->validator->validate($validationSubject);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Payment data object should be provided
     */
    public function testValidateReadPaymentException()
    {
        $validationSubject = [
            'response' => ['data'],
            'amount' => 1000,
            'payment' => null
        ];

        $this->validator->validate($validationSubject);
    }

    /**
     * @param array $validationSubject
     * @param bool $isValid
     * @param array $fails
     *
     * @dataProvider dataProviderValidate
     */
    public function testValidate(array $validationSubject, $isValid, array $fails)
    {
        /** @var ResultInterface|\PHPUnit_Framework_MockObject_MockObject $resultMock */
        $resultMock = $this->getMockBuilder(\Magento\Payment\Gateway\Validator\ResultInterface::class)
            ->getMockForAbstractClass();

        $this->resultInterfaceFactory->expects($this->once())
            ->method('create')
            ->with(['isValid' => $isValid, 'failsDescription' => $fails, 'errorCodes' => []])
            ->willReturn($resultMock);

        $actualMock = $this->validator->validate($validationSubject);

        $this->assertEquals($resultMock, $actualMock);
    }

    /**
     * Case 1. Success
     * Case 2. Errors in response
     * Case 3. Wrong amount
     * Case 4. TransactionStatus error
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProviderValidate()
    {
        $transactionId = 12345678;

        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMockForAbstractClass();

        $payment = $this->getMockBuilder(\Magento\Sales\Model\Order\Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDO->expects($this->any())
            ->method('getPayment')
            ->willReturn($payment);
        $payment->expects($this->any())
            ->method('getParentTransactionId')
            ->willReturn($transactionId);

        return [
            [
                'validationSubject' => [
                    'response' => [
                        'Errors' => null,
                        'Refund' => [
                            'TransactionID' => $transactionId,
                            'TotalAmount' => 1000
                        ],
                        'TransactionStatus' => true,
                        'TransactionID' => 87654321,
                        'ResponseCode' => '00',
                        'AuthorisationCode' => '12345678',
                        'ResponseMessage' => 'A2000'
                    ],
                    'amount' => self::AMOUNT_DATA,
                    'payment' => $paymentDO,
                ],
                'isValid' => true,
                'fails' => []
            ],
            [
                'validationSubject' => [
                    'response' => [
                        'Errors' => 'V6018',
                        'Refund' => [
                            'TransactionID' => $transactionId,
                            'TotalAmount' => 1000
                        ],
                        'TransactionStatus' => true,
                        'TransactionID' => 87654321,
                        'ResponseCode' => null,
                        'AuthorisationCode' => '12345678',
                        'ResponseMessage' => 'A2000'
                    ],
                    'amount' => self::AMOUNT_DATA,
                    'payment' => $paymentDO,
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
            [
                'validationSubject' => [
                    'response' => [
                        'Errors' => null,
                        'Refund' => [
                            'TransactionID' => $transactionId,
                            'TotalAmount' => 0
                        ],
                        'TransactionStatus' => true,
                        'TransactionID' => 87654321,
                        'ResponseCode' => null,
                        'AuthorisationCode' => '12345678',
                        'ResponseMessage' => 'A2000'
                    ],
                    'amount' => self::AMOUNT_DATA,
                    'payment' => $paymentDO,
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
            [
                'validationSubject' => [
                    'response' => [
                        'Errors' => null,
                        'Refund' => [
                            'TransactionID' => $transactionId,
                            'TotalAmount' => 1000
                        ],
                        'TransactionStatus' => false,
                        'TransactionID' => 87654321,
                        'ResponseCode' => null,
                        'AuthorisationCode' => '12345678',
                        'ResponseMessage' => 'A2000'
                    ],
                    'amount' => self::AMOUNT_DATA,
                    'payment' => $paymentDO,
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
            [
                'validationSubject' => [
                    'response' => [
                        'Errors' => null,
                        'Refund' => [
                            'TransactionID' => $transactionId,
                            'TotalAmount' => 1000
                        ],
                        'TransactionStatus' => true,
                        'TransactionID' => 87654321,
                        'ResponseCode' => null,
                        'AuthorisationCode' => null,
                        'ResponseMessage' => null
                    ],
                    'amount' => self::AMOUNT_DATA,
                    'payment' => $paymentDO,
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
            [
                'validationSubject' => [
                    'response' => [
                        'Errors' => null,
                        'Refund' => [
                            'TransactionID' => 12348765,
                            'TotalAmount' => 1000
                        ],
                        'TransactionStatus' => true,
                        'TransactionID' => 87654321,
                        'ResponseCode' => null,
                        'AuthorisationCode' => '12345678',
                        'ResponseMessage' => 'A2000'
                    ],
                    'amount' => self::AMOUNT_DATA,
                    'payment' => $paymentDO,
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
        ];
    }
}
