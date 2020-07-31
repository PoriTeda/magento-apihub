<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Validator\Soap;

use Magento\Cybersource\Gateway\Validator\Soap\SubscriptionIdValidator;
use Magento\Payment\Gateway\Validator\Result;
use Magento\Cybersource\Gateway\Request\Soap\AuthorizeDataBuilder;

class SubscriptionIdValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resultFactory;

    /**
     * @var \Magento\Cybersource\Gateway\Validator\Soap\SubscriptionIdValidator
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

        $this->validator = new SubscriptionIdValidator($this->resultFactory);
    }

    public function testValidateNoSubscriptionId()
    {
        $response = [];

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

    public function testValidate()
    {
        $response = [
            'paySubscriptionCreateReply' => ['subscriptionID' => '1111']
        ];

        $this->resultFactory->expects(static::once())
            ->method('create')
            ->with([
                'isValid' => true,
                'failsDescription' => [__('Your payment has been declined. Please try again.')],
                'errorCodes' => []
            ])
            ->willReturn(new Result(false, [__('Your payment has been declined. Please try again.')]));

        $result = $this->validator->validate(['response' => $response]);

        static::assertFalse($result->isValid());
    }
}
