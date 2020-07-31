<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Gateway\Validator;

use Magento\Payment\Gateway\Validator\Result;
use Magento\Worldpay\Gateway\Validator\AcceptValidator;

class AcceptValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDO;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resultFactory;

    /**
     * @var AcceptValidator
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

        $this->validator = new AcceptValidator($this->resultFactory);
    }

    public function testValidateFails()
    {
        $subject = [
            'payment' => $this->paymentDO,
            'response' => [
                'authCurrency' => 'PE',
                'authCost' => '10.01',
                'authMode' => 'G'
            ]
        ];

        $expectedFails = [
            __('Currency doesn\'t match.'),
            __('Amount doesn\'t match.'),
            __('Not supported response.')
        ];

        $orderAdapter = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\OrderAdapterInterface::class
        )->getMockForAbstractClass();

        $this->paymentDO->expects(static::any())
            ->method('getOrder')
            ->willReturn($orderAdapter);
        $orderAdapter->expects(static::once())
            ->method('getCurrencyCode')
            ->willReturn('USD');
        $orderAdapter->expects(static::once())
            ->method('getGrandTotalAmount')
            ->willReturn(10.00);
        $this->resultFactory->expects(static::once())
            ->method('create')
            ->with(
                [
                    'isValid' => false,
                    'failsDescription' => $expectedFails,
                    'errorCodes' => []
                ]
            )
            ->willReturn(
                new Result(
                    false,
                    $expectedFails
                )
            );

        $result = $this->validator->validate($subject);

        static::assertEquals($expectedFails, $result->getFailsDescription());
        static::assertFalse($result->isValid());
    }
}
