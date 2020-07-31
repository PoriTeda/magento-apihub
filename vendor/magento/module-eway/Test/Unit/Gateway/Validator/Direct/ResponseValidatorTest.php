<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Validator\Direct;

use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Eway\Gateway\Validator\Direct\ResponseValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

/**
 * Class ResponseValidatorTest
 */
class ResponseValidatorTest extends \PHPUnit\Framework\TestCase
{
    const AMOUNT_DATA = 66;

    const TRANSACTION_ID_DATA = '741365696';

    const AUTHORISATION_CODE_DATA = '78946';

    const RESPONSE_MESSAGE_DATA = 'A0000,F7000,F9021';

    const CARD_DETAILS_DATA = 'test-card-data';

    /**
     * @var ResponseValidator
     */
    private $responseValidator;

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

        $this->responseValidator = new ResponseValidator($this->resultInterfaceFactoryMock);
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

        $this->responseValidator->validate($validationSubject);
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

        $this->responseValidator->validate($validationSubject);
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

        $actualMock = $this->responseValidator->validate($validationSubject);

        $this->assertEquals($resultMock, $actualMock);
    }

    /**
     * @return array
     */
    public function dataProviderTestValidate()
    {
        return [
            [
                'validationSubject' => [
                    'response' => [
                        'Errors' => null,
                        'Payment' => [
                            'TotalAmount' => (string) self::AMOUNT_DATA * 100
                        ],
                        'TransactionType' => 'Purchase',
                        'TransactionStatus' => true,
                        'TransactionID' => self::TRANSACTION_ID_DATA,
                        'ResponseCode' => '00',
                        'AuthorisationCode' => self::AUTHORISATION_CODE_DATA,
                        'ResponseMessage' => self::RESPONSE_MESSAGE_DATA,
                        'Customer' => [
                            'CardDetails' => [
                                self::CARD_DETAILS_DATA
                            ]
                        ]
                    ],
                    'amount' => self::AMOUNT_DATA
                ],
                'isValid' => true,
                'fails' => []
            ],
            [
                'validationSubject' => [
                    'response' => [
                        'Errors' => null,
                        'Payment' => [
                            'TotalAmount' => (string) (self::AMOUNT_DATA * 100 + 20)
                        ],
                        'TransactionType' => 'Purchase',
                        'TransactionStatus' => true,
                        'TransactionID' => self::TRANSACTION_ID_DATA,
                        'ResponseCode' => '00',
                        'AuthorisationCode' => self::AUTHORISATION_CODE_DATA,
                        'ResponseMessage' => self::RESPONSE_MESSAGE_DATA,
                        'Customer' => [
                            'CardDetails' => [
                                self::CARD_DETAILS_DATA
                            ]
                        ]
                    ],
                    'amount' => self::AMOUNT_DATA
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
            [
                'validationSubject' => [
                    'response' => [
                        'Errors' => null,
                        'Payment' => [
                            'TotalAmount' => (string) self::AMOUNT_DATA * 100
                        ],
                        'TransactionType' => 'Purchase',
                        'TransactionStatus' => true,
                        'TransactionID' => self::TRANSACTION_ID_DATA,
                        'ResponseCode' => '00',
                        'AuthorisationCode' => self::AUTHORISATION_CODE_DATA,
                        'Customer' => [
                            'CardDetails' => [
                                self::CARD_DETAILS_DATA
                            ]
                        ]
                    ],
                    'amount' => self::AMOUNT_DATA
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
            [
                'validationSubject' => [
                    'response' => [],
                    'amount' => self::AMOUNT_DATA
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
        ];
    }
}
