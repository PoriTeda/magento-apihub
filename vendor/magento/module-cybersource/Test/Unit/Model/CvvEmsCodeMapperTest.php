<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Model;

use Magento\Cybersource\Model\CvvEmsCodeMapper;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class CvvEmsCodeMapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    private static $paymentMethodCode = 'cybersource';
    
    /**
     * @var CvvEmsCodeMapper
     */
    private $mapper;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->mapper = new CvvEmsCodeMapper();
    }

    /**
     * Checks different variations for cvv codes mapping.
     *
     * @covers \Magento\Cybersource\Model\CvvEmsCodeMapper::getCode
     * @param string $authCvResult
     * @param string $expected
     * @dataProvider getCodeDataProvider
     */
    public function testGetCode($authCvResult, $expected)
    {
        /** @var OrderPaymentInterface|MockObject $orderPayment */
        $orderPayment = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderPayment->expects(self::once())
            ->method('getMethod')
            ->willReturn(self::$paymentMethodCode);

        $orderPayment->expects(self::once())
            ->method('getAdditionalInformation')
            ->willReturn(['auth_cv_result' => $authCvResult]);

        self::assertEquals($expected, $this->mapper->getCode($orderPayment));
    }

    /**
     * Checks a test case, when payment order is not Cybersource payment method.
     *
     * @covers \Magento\Cybersource\Model\CvvEmsCodeMapper::getCode
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "some_payment" does not supported by Cybersource CVV mapper.
     */
    public function testGetCodeWithException()
    {
        /** @var OrderPaymentInterface|MockObject $orderPayment */
        $orderPayment = $this->getMockBuilder(OrderPaymentInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $orderPayment->expects(self::exactly(2))
            ->method('getMethod')
            ->willReturn('some_payment');

        $this->mapper->getCode($orderPayment);
    }

    /**
     * Gets variations of cvv codes and expected mapping result.
     *
     * @return array
     */
    public function getCodeDataProvider()
    {
        return [
            ['authCvResult' => '', 'expected' => 'P'],
            ['authCvResult' => null, 'expected' => 'P'],
            ['authCvResult' => 'D', 'expected' => 'P'],
            ['authCvResult' => 'I', 'expected' => 'N'],
            ['authCvResult' => 'M', 'expected' => 'M'],
            ['authCvResult' => 'N', 'expected' => 'N'],
            ['authCvResult' => 'P', 'expected' => 'P'],
            ['authCvResult' => 'S', 'expected' => 'S'],
            ['authCvResult' => 'U', 'expected' => 'U'],
            ['authCvResult' => 'X', 'expected' => 'P'],
            ['authCvResult' => '1', 'expected' => 'P'],
            ['authCvResult' => '2', 'expected' => 'P'],
            ['authCvResult' => '3', 'expected' => 'P'],
        ];
    }
}
