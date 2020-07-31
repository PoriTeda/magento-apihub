<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Cybersource\Test\Unit\Gateway\Request\SilentOrder;

use Magento\Cybersource\Gateway\Request\SilentOrder\SignCompositeDecorator;
use Magento\Cybersource\Gateway\Request\SilentOrder\CcDataBuilder;

class SignCompositeDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\ObjectManager\TMapFactory | \PHPUnit_Framework_MockObject_MockObject
     */
    private $tMapFactory;

    /**
     * @var \Magento\Framework\ObjectManager\TMap | \PHPUnit_Framework_MockObject_MockObject
     */
    private $tmap;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTimeFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $dateTime;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $configMock;

    protected function setUp()
    {
        $this->tMapFactory = $this->getMockBuilder(\Magento\Framework\ObjectManager\TMapFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->tmap = $this->getMockBuilder(\Magento\Framework\ObjectManager\TMap::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->tMapFactory->expects(static::once())
            ->method('create')
            ->willReturn($this->tmap);

        $this->dateTimeFactory = $this->getMockBuilder(\Magento\Framework\Intl\DateTimeFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateTime = $this->getMockBuilder(\DateTime::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dateTimeFactory->expects(static::once())
            ->method('create')
            ->willReturn($this->dateTime);

        $this->configMock = $this->getMockBuilder(\Magento\Payment\Gateway\ConfigInterface::class)
            ->getMockForAbstractClass();
    }

    public function testBuild()
    {
        $subject = ['payment' => $this->getPaymentDO()];

        $this->tmap->expects(static::once())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator($this->getExpectedBuilders()));
        $this->dateTime->expects(static::once())
            ->method('format')
            ->with(SignCompositeDecorator::SIGNED_DATE_TIME_FORMAT)
            ->willReturn('2013-09-17T08:17:07Z');
        $this->configMock->expects(static::once())
            ->method('getValue')
            ->with('secret_key', 1)
            ->willReturn('SECRET');

        $signBuilder = new SignCompositeDecorator(
            $this->dateTimeFactory,
            $this->configMock,
            ['amount', 'cc'],
            $this->tMapFactory
        );

        $result = $signBuilder->build($subject);
        static::assertSame($this->getExpectedResult(), $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getPaymentDO()
    {
        $order = $this->getMockBuilder(\Magento\Payment\Gateway\Data\OrderAdapterInterface::class)
            ->getMock();
        $paymentDO = $this->getMockBuilder(\Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class)
            ->getMock();

        $paymentDO->expects(static::once())
            ->method('getOrder')
            ->willReturn($order);

        $order->expects(static::once())
            ->method('getStoreId')
            ->willReturn(1);

        return $paymentDO;
    }

    /**
     * @return array
     */
    private function getExpectedBuilders()
    {
        $amountBuilder = $this->getMockBuilder(\Magento\Payment\Gateway\Request\BuilderInterface::class)
            ->getMockForAbstractClass();
        $ccBuilder = $this->getMockBuilder(\Magento\Payment\Gateway\Request\BuilderInterface::class)
            ->getMockForAbstractClass();

        $amountBuilder->expects(static::once())
            ->method('build')
            ->willReturn(['amount' => '0.00', 'currency' => 'USD']);
        $ccBuilder->expects(static::once())
            ->method('build')
            ->willReturn(
                [
                    CcDataBuilder::CARD_TYPE => '001',
                    CcDataBuilder::CARD_NUMBER => '',
                    CcDataBuilder::CARD_EXPIRY_DATE => '',
                    CcDataBuilder::CARD_CVN => ''
                ]
            );

        return [$ccBuilder, $amountBuilder];
    }

    /**
     * @return array
     */
    private function getExpectedResult()
    {
        return [
            'amount' => '0.00',
            'currency' => 'USD',
            SignCompositeDecorator::SIGNED_DATE_TIME => '2013-09-17T08:17:07Z',
            SignCompositeDecorator::UNSIGNED_FIELD_NAMES => implode(
                ',',
                [
                    CcDataBuilder::CARD_TYPE,
                    CcDataBuilder::CARD_NUMBER,
                    CcDataBuilder::CARD_EXPIRY_DATE,
                    CcDataBuilder::CARD_CVN
                ]
            ),
            SignCompositeDecorator::SIGNED_FIELD_NAMES => implode(
                ',',
                [
                    'amount',
                    'currency',
                    SignCompositeDecorator::SIGNED_DATE_TIME,
                    SignCompositeDecorator::UNSIGNED_FIELD_NAMES,
                    SignCompositeDecorator::SIGNED_FIELD_NAMES
                ]
            ),
            CcDataBuilder::CARD_TYPE => '001',
            CcDataBuilder::CARD_NUMBER => '',
            CcDataBuilder::CARD_EXPIRY_DATE => '',
            CcDataBuilder::CARD_CVN => '',
            SignCompositeDecorator::SIGNATURE => $this->getSignature()
        ];
    }

    /**
     * @return string
     */
    private function getSignature()
    {
        return
            base64_encode(
                hash_hmac(
                    'sha256',
                    sprintf(
                        '%s=%s,%s=%s,%s=%s,%s=%s,%s=%s',
                        'amount',
                        '0.00',
                        'currency',
                        'USD',
                        SignCompositeDecorator::SIGNED_DATE_TIME,
                        '2013-09-17T08:17:07Z',
                        SignCompositeDecorator::UNSIGNED_FIELD_NAMES,
                        implode(
                            ',',
                            [
                                CcDataBuilder::CARD_TYPE,
                                CcDataBuilder::CARD_NUMBER,
                                CcDataBuilder::CARD_EXPIRY_DATE,
                                CcDataBuilder::CARD_CVN
                            ]
                        ),
                        SignCompositeDecorator::SIGNED_FIELD_NAMES,
                        implode(
                            ',',
                            [
                                'amount',
                                'currency',
                                SignCompositeDecorator::SIGNED_DATE_TIME,
                                SignCompositeDecorator::UNSIGNED_FIELD_NAMES,
                                SignCompositeDecorator::SIGNED_FIELD_NAMES
                            ]
                        )
                    ),
                    'SECRET',
                    true
                )
            );
    }
}
