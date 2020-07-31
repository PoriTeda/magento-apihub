<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Test\Unit\Gateway\Request\RemoteAdmin;

use Magento\Worldpay\Gateway\Request\RemoteAdmin\RefundDataBuilder;

class RefundDataBuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    /**
     * @var RefundDataBuilder
     */
    private $builder;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $orderAdapter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $paymentDO;

    protected function setUp()
    {
        $this->config = $this->getMockBuilder(
            \Magento\Payment\Gateway\ConfigInterface::class
        )
            ->getMockForAbstractClass();
        $this->paymentDO = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\PaymentDataObjectInterface::class
        )
            ->getMockForAbstractClass();
        $this->orderAdapter = $this->getMockBuilder(
            \Magento\Payment\Gateway\Data\OrderAdapterInterface::class
        )
            ->getMockForAbstractClass();

        $this->builder = new RefundDataBuilder($this->config);
    }

    public function testBuild()
    {
        $storeId = 1;

        $expectation = [
            'authPW' => 'PASSWORD',
            'instId' => 'ADMIN_ID',
            'testMode' => 100,
            'cartId' => 'Refund',
            'op' => 'refund-partial',
            'transId' => '1001',
            'amount' => '10.00',
            'currency' => 'PEP'
        ];

        $paymentInfo = $this->getMockBuilder(
            \Magento\Sales\Model\Order\Payment::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentDO->expects(static::any())
            ->method('getOrder')
            ->willReturn($this->orderAdapter);
        $this->paymentDO->expects(static::any())
            ->method('getPayment')
            ->willReturn($paymentInfo);
        $paymentInfo->expects(static::any())
            ->method('getParentTransactionId')
            ->willReturn('1001');
        $this->orderAdapter->expects(static::any())
            ->method('getStoreId')
            ->willReturn($storeId);
        $this->orderAdapter->expects(static::any())
            ->method('getCurrencyCode')
            ->willReturn('PEP');
        $this->config->expects(static::any())
            ->method('getValue')
            ->willReturnMap(
                [
                    ['auth_password', $storeId, 'PASSWORD'],
                    ['admin_installation_id', $storeId, 'ADMIN_ID'],
                    ['test_mode', $storeId, '1']
                ]
            );

        static::assertEquals(
            $expectation,
            $this->builder->build(
                [
                    'payment' => $this->paymentDO,
                    'amount' => 10.001
                ]
            )
        );
    }
}
