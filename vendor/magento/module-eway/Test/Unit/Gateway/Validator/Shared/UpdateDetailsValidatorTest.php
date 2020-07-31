<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Validator\Shared;

use Magento\Eway\Gateway\Validator\Shared\UpdateDetailsValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

/**
 * Class UpdateDetailsValidatorTest
 */
class UpdateDetailsValidatorTest extends \PHPUnit\Framework\TestCase
{
    const AMOUNT_DATA = 66;

    const AUTHORISATION_CODE_DATA = '78946';

    const RESPONSE_MESSAGE_DATA = 'A0000,F7000,F9021';

    const TRANSACTION_ID_DATA = '741365696';

    /**
     * @var UpdateDetailsValidator
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
        $this->resultInterfaceFactoryMock = $this
            ->getMockBuilder(\Magento\Payment\Gateway\Validator\ResultInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMockForAbstractClass();

        $this->validator = new UpdateDetailsValidator($this->resultInterfaceFactoryMock);
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
     * @expectedExceptionMessage Transaction id does not exist
     */
    public function testValidateGetTransactionIdException()
    {
        $validationSubject = [
            'response' => ['data'],
            'amount' => 100,
            'transaction_id' => null
        ];

        $this->validator->validate($validationSubject);
    }

    /**
     * @param array $validationSubject
     * @param bool $isValid
     * @param array $fails
     *
     * @dataProvider validateDataProvider
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
     * Case 1. Success
     * Case 2. Errors in response
     * Case 3. Wrong total amount
     * Case 4. Wrong transaction data
     * Case 5. Wrong response data
     * Case 6. Wrong transaction id
     *
     * @return array
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function validateDataProvider()
    {
        return [
            [
                'validationSubject' => [
                    'request' => [
                        'transaction_id' => self::TRANSACTION_ID_DATA,
                    ],
                    'response' => [
                        'Errors' => null,
                        'TotalAmount' => (string) self::AMOUNT_DATA * 100,
                        'TransactionStatus' => true,
                        'ResponseCode' => '00',
                        'AuthorisationCode' => self::AUTHORISATION_CODE_DATA,
                        'ResponseMessage' => self::RESPONSE_MESSAGE_DATA,
                        'TransactionID' => self::TRANSACTION_ID_DATA,
                    ],
                    'amount' => self::AMOUNT_DATA
                ],
                'isValid' => true,
                'fails' => []
            ],
            [
                'validationSubject' => [
                    'request' => [
                        'transaction_id' => self::TRANSACTION_ID_DATA,
                    ],
                    'response' => [
                        'Errors' => ['V6018'],
                        'TotalAmount' => (string) self::AMOUNT_DATA * 100,
                        'TransactionStatus' => true,
                        'ResponseCode' => '00',
                        'AuthorisationCode' => self::AUTHORISATION_CODE_DATA,
                        'ResponseMessage' => self::RESPONSE_MESSAGE_DATA,
                        'TransactionID' => self::TRANSACTION_ID_DATA,
                    ],
                    'amount' => self::AMOUNT_DATA
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
            [
                'validationSubject' => [
                    'request' => [
                        'transaction_id' => self::TRANSACTION_ID_DATA,
                    ],
                    'response' => [
                        'Errors' => null,
                        'TotalAmount' => (string)(self::AMOUNT_DATA * 100 + 20),
                        'TransactionStatus' => true,
                        'ResponseCode' => '00',
                        'AuthorisationCode' => self::AUTHORISATION_CODE_DATA,
                        'ResponseMessage' => self::RESPONSE_MESSAGE_DATA,
                        'TransactionID' => self::TRANSACTION_ID_DATA,
                    ],
                    'amount' => self::AMOUNT_DATA
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
            [
                'validationSubject' => [
                    'request' => [
                        'transaction_id' => self::TRANSACTION_ID_DATA,
                    ],
                    'response' => [
                        'Errors' => null,
                        'TotalAmount' => (string) self::AMOUNT_DATA * 100,
                        'TransactionStatus' => false,
                        'ResponseCode' => '00',
                        'AuthorisationCode' => self::AUTHORISATION_CODE_DATA,
                        'ResponseMessage' => self::RESPONSE_MESSAGE_DATA,
                        'TransactionID' => self::TRANSACTION_ID_DATA,
                    ],
                    'amount' => self::AMOUNT_DATA
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
            [
                'validationSubject' => [
                    'request' => [
                        'transaction_id' => self::TRANSACTION_ID_DATA,
                    ],
                    'response' => [
                        'Errors' => null,
                        'TotalAmount' => (string) self::AMOUNT_DATA * 100,
                        'TransactionStatus' => true,
                        'ResponseCode' => null,
                        'AuthorisationCode' => null,
                        'ResponseMessage' => null,
                        'TransactionID' => self::TRANSACTION_ID_DATA,
                    ],
                    'amount' => self::AMOUNT_DATA
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
            [
                'validationSubject' => [
                    'request' => [
                        'transaction_id' => self::TRANSACTION_ID_DATA,
                    ],
                    'response' => [
                        'Errors' => null,
                        'TotalAmount' => (string) self::AMOUNT_DATA * 100,
                        'TransactionStatus' => true,
                        'ResponseCode' => '00',
                        'AuthorisationCode' => self::AUTHORISATION_CODE_DATA,
                        'ResponseMessage' => self::RESPONSE_MESSAGE_DATA,
                        'TransactionID' => null,
                    ],
                    'amount' => self::AMOUNT_DATA
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ],
        ];
    }
}
