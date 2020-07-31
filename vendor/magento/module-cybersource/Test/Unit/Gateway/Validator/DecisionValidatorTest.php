<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Validator;

use Magento\Cybersource\Gateway\Validator\DecisionValidator;
use Magento\Payment\Gateway\Validator\Result;

class DecisionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resultFactory;

    /**
     * @var \Magento\Cybersource\Gateway\Validator\DecisionValidator
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

        $this->validator = new DecisionValidator($this->resultFactory);
    }

    public function testValidateNoDecision()
    {
        $response = ['data', 'data2'];

        $this->resultFactory->expects(static::once())
            ->method('create')
            ->with([
                'isValid' => false,
                'failsDescription' => [__('Your payment has been declined. Please try again.')],
                'errorCodes' => []
            ])
            ->willReturn(new Result(false, [__('Your payment has been declined. Please try again.')]));

        $result = $this->validator->validate(['response' => $response]);
        static::assertFalse($result->isValid());
    }

    /**
     * @param $response
     * @dataProvider invalidResponseDataProvider
     */
    public function testValidateWrongDecision($response)
    {
        $this->resultFactory->expects(static::once())
            ->method('create')
            ->with([
                'isValid' => false,
                'failsDescription' => [__('Your payment has been declined. Please try again.')],
                'errorCodes' => []
            ])
            ->willReturn(new Result(false, [__('Your payment has been declined. Please try again.')]));

        $result = $this->validator->validate(['response' => $response]);
        static::assertFalse($result->isValid());
    }

    public function invalidResponseDataProvider()
    {
        return [
            [[DecisionValidator::DECISION => 'data', DecisionValidator::REASON_CODE => '',]],
            [
                [
                    DecisionValidator::DECISION => 'REJECT',
                    DecisionValidator::REASON_CODE => 111
                ]
            ],
        ];
    }

    /**
     * @param $response
     * @dataProvider validResponseDataProvider
     */
    public function testValidate($response)
    {
        $this->resultFactory->expects(static::once())
            ->method('create')
            ->with([
                'isValid' => true,
                'failsDescription' => [__('Your payment has been declined. Please try again.')],
                'errorCodes' => []
            ])
            ->willReturn(new Result(true, []));

        $result = $this->validator->validate(['response' => $response]);
        static::assertTrue($result->isValid());
    }

    public function validResponseDataProvider()
    {
        return [
            [
                [
                    DecisionValidator::DECISION => 'ACCEPT',
                    DecisionValidator::REASON_CODE => ''
                ]
            ],
            [
                [
                    DecisionValidator::DECISION => 'REVIEW',
                    DecisionValidator::REASON_CODE => ''
                ]
            ],
            [
                [
                    DecisionValidator::DECISION => 'REJECT',
                    DecisionValidator::REASON_CODE => DecisionValidator::REASON_AUTH_REVERSED
                ]
            ]
        ];
    }
}
