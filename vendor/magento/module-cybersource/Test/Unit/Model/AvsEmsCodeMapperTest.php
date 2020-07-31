<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Model;

use Magento\Cybersource\Model\AvsEmsCodeMapper;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class AvsEmsCodeMapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    private static $paymentMethodCode = 'cybersource';

    /**
     * @var AvsEmsCodeMapper
     */
    private $mapper;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->mapper = new AvsEmsCodeMapper();
    }

    /**
     * Checks different variations for AVS codes mapping.
     *
     * @covers \Magento\Cybersource\Model\AvsEmsCodeMapper::getCode
     * @param string $authAvsCode
     * @param string $expected
     * @dataProvider getCodeDataProvider
     */
    public function testGetCode($authAvsCode, $expected)
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
            ->willReturn([
                'auth_avs_code' => $authAvsCode
            ]);

        self::assertEquals($expected, $this->mapper->getCode($orderPayment));
    }

    /**
     * Checks a test case, when payment order is not Cybersource payment method.
     *
     * @covers \Magento\Cybersource\Model\AvsEmsCodeMapper::getCode
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The "some_payment" does not supported by Cybersource AVS mapper.
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
     * Gets list of AVS codes.
     *
     * @return array
     */
    public function getCodeDataProvider()
    {
        return [
            ['authAvsCode' => null, 'expected' => ''],
            ['authAvsCode' => 'A', 'expected' => 'A'],
            ['authAvsCode' => 'B', 'expected' => 'B'],
            ['authAvsCode' => 'C', 'expected' => 'C'],
            ['authAvsCode' => 'D', 'expected' => 'D'],
            ['authAvsCode' => 'E', 'expected' => 'E'],
            ['authAvsCode' => 'F', 'expected' => 'Z'],
            ['authAvsCode' => 'G', 'expected' => 'G'],
            ['authAvsCode' => 'H', 'expected' => 'Y'],
            ['authAvsCode' => 'I', 'expected' => 'I'],
            ['authAvsCode' => 'J', 'expected' => 'Y'],
            ['authAvsCode' => 'K', 'expected' => 'N'],
            ['authAvsCode' => 'L', 'expected' => 'Z'],
            ['authAvsCode' => 'M', 'expected' => 'M'],
            ['authAvsCode' => 'N', 'expected' => 'N'],
            ['authAvsCode' => 'O', 'expected' => 'A'],
            ['authAvsCode' => 'P', 'expected' => 'Z'],
            ['authAvsCode' => 'R', 'expected' => 'Y'],
            ['authAvsCode' => 'S', 'expected' => 'S'],
            ['authAvsCode' => 'T', 'expected' => 'A'],
            ['authAvsCode' => 'U', 'expected' => 'U'],
            ['authAvsCode' => 'V', 'expected' => 'Y'],
            ['authAvsCode' => 'W', 'expected' => 'W'],
            ['authAvsCode' => 'X', 'expected' => 'X'],
            ['authAvsCode' => 'Y', 'expected' => 'Y'],
            ['authAvsCode' => 'Z', 'expected' => 'Z'],
            ['authAvsCode' => '1', 'expected' => 'S'],
            ['authAvsCode' => '2', 'expected' => 'E'],
            ['authAvsCode' => '3', 'expected' => 'Y'],
            ['authAvsCode' => '4', 'expected' => 'N'],
        ];
    }
}
