<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Eway\Test\Unit\Gateway\Validator\Shared;

use Magento\Eway\Gateway\Validator\Shared\AccessCodeValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

class AccessCodeValidatorTest extends \PHPUnit\Framework\TestCase
{
    const AMOUNT_DATA = 66;

    const ACCESS_CODE = 'access_code';

    /**
     * @var AccessCodeValidator
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

        $this->validator = new AccessCodeValidator($this->resultInterfaceFactoryMock);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Response does not exist
     */
    public function testValidateReadResponseException()
    {
        $validationSubject = [];

        $this->validator->validate($validationSubject);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Amount should be provided
     */
    public function testValidateReadAmountException()
    {
        $validationSubject = ['response' => ['data']];

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
     * Case 3. Wrong amount
     * Case 4. Empty access code
     *
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            [
                'validationSubject' => [
                    'response' => [
                        'Errors' => null,
                        'Payment' => [
                            'TotalAmount' => (string) self::AMOUNT_DATA * 100
                        ],
                        'AccessCode' => self::ACCESS_CODE
                    ],
                    'amount' => self::AMOUNT_DATA
                ],
                'isValid' => true,
                'fails' => []
            ],
            [
                'validationSubject' => [
                    'response' => [
                        'Errors' => ['V6018'],
                        'Payment' => [
                            'TotalAmount' => (string) self::AMOUNT_DATA * 100
                        ],
                        'AccessCode' => self::ACCESS_CODE
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
                            'TotalAmount' => (string) (self::AMOUNT_DATA * 100 + 20)
                        ],
                        'AccessCode' => self::ACCESS_CODE
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
                        'AccessCode' => ''
                    ],
                    'amount' => self::AMOUNT_DATA
                ],
                'isValid' => false,
                'fails' => ['Transaction has been declined. Please try again later.']
            ]
        ];
    }
}
