<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Cybersource\Test\Unit\Gateway\Validator;

use Magento\Cybersource\Gateway\Validator\DecisionValidator;
use Magento\Cybersource\Gateway\Validator\CancelValidator;
use Magento\Payment\Gateway\Validator\Result;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;

/**
 * Test for Magento\Cybersource\Gateway\Validator\CancelValidator class.
 */
class CancelValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ResultInterfaceFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    private $cancelResultFactory;

    /**
     * @var ResultInterfaceFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    private $decisionResultFactory;

    /**
     * @var DecisionValidator
     */
    private $decisionValidator;

    /**
     * @var CancelValidator
     */
    private $cancelValidator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->cancelResultFactory = $this->getMockBuilder(ResultInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->decisionResultFactory = clone $this->cancelResultFactory;
        $this->decisionValidator = new DecisionValidator($this->decisionResultFactory);
        $this->cancelValidator = new CancelValidator(
            $this->cancelResultFactory,
            $this->decisionValidator
        );
    }

    /**
     * Test validator in case of wrong response.
     *
     * @return void
     */
    public function testValidateNoDecision(): void
    {
        $response = ['data', 'data2'];

        $this->assertFalse(
            $this->validateResponse($response, false, false, $this->once())->isValid()
        );
    }

    /**
     * Test validator for the negative decision.
     *
     * @param array $response
     * @return void
     * @dataProvider negativeResponseDataProvider
     */
    public function testValidateNegativeDecision(array $response): void
    {
        $this->assertFalse(
            $this->validateResponse($response, false, false, $this->once())->isValid()
        );
    }

    /**
     * Returns result of response validation.
     *
     * @param array $response
     * @param bool $decisionResult
     * @param bool $cancelResult
     * @param \PHPUnit\Framework\MockObject\Matcher\InvokedCount $cancelResultCall
     * @return ResultInterface
     */
    private function validateResponse(
        array $response,
        bool $decisionResult,
        bool $cancelResult,
        \PHPUnit\Framework\MockObject\Matcher\InvokedCount $cancelResultCall
    ): ResultInterface {
        $this->decisionResultFactory->expects($this->once())
            ->method('create')
            ->with([
                'isValid' => $decisionResult,
                'failsDescription' => [__('Your payment has been declined. Please try again.')],
                'errorCodes' => [],
            ])
            ->willReturn(new Result($decisionResult, [__('Your payment has been declined. Please try again.')]));

        $this->cancelResultFactory->expects($cancelResultCall)
            ->method('create')
            ->with([
                'isValid' => $cancelResult,
                'failsDescription' => [__('Your payment has been declined. Please try again.')],
                'errorCodes' => [],
            ])
            ->willReturn(new Result($cancelResult, [__('Your payment has been declined. Please try again.')]));

        return $this->cancelValidator->validate(['response' => $response]);
    }

    /**
     * @return array
     */
    public function negativeResponseDataProvider(): array
    {
        return [
            [
                [
                    DecisionValidator::DECISION => 'data',
                    DecisionValidator::REASON_CODE => '',
                ],
            ],
            [
                [
                    DecisionValidator::DECISION => 'REJECT',
                    DecisionValidator::REASON_CODE => 111,
                ],
            ],
        ];
    }

    /**
     * Test validator with acceptable response.
     *
     * @param array $response
     * @param bool $decisionResult
     * @param \PHPUnit\Framework\MockObject\Matcher\InvokedCount $cancelResultCall
     * @dataProvider acceptableResponseDataProvider
     * @return void
     */
    public function testValidate(
        array $response,
        bool $decisionResult,
        \PHPUnit\Framework\MockObject\Matcher\InvokedCount $cancelResultCall
    ): void {
        $this->assertTrue(
            $this->validateResponse($response, $decisionResult, true, $cancelResultCall)->isValid()
        );
    }

    /**
     * @return array
     */
    public function acceptableResponseDataProvider(): array
    {
        return [
            [
                [
                    DecisionValidator::DECISION => 'ACCEPT',
                    DecisionValidator::REASON_CODE => '',
                ],
                true,
                $this->never(),
            ],
            [
                [
                    DecisionValidator::DECISION => 'REJECT',
                    DecisionValidator::REASON_CODE => 102,
                ],
                false,
                $this->once(),
            ],
        ];
    }
}
