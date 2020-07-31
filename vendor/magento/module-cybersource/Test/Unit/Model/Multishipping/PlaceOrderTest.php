<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Cybersource\Test\Unit\Model\Multishipping;

use Magento\Cybersource\Model\Multishipping\PlaceOrder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterfaceFactory;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Vault\Api\Data\PaymentTokenInterface;

/**
 * Tests Magento\Cybersource\Model\Multishipping\PlaceOrder.
 */
class PlaceOrderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OrderManagementInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderManagement;

    /**
     * @var OrderPaymentExtensionInterfaceFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $orderPaymentExtensionInterfaceFactory;

    /**
     * @var PlaceOrder
     */
    private $placeOrder;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->orderManagement = $this->getMockForAbstractClass(OrderManagementInterface::class);
        $this->orderPaymentExtensionInterfaceFactory = $this->getMockBuilder(
            OrderPaymentExtensionInterfaceFactory::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $this->placeOrder = new PlaceOrder($this->orderManagement, $this->orderPaymentExtensionInterfaceFactory);
    }

    /**
     * Case when first order from list fails to place and exception is thrown.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage place order error
     * @return void
     */
    public function testPlaceFirstOrderWithError()
    {
        $firstOrder = $this->getMockForAbstractClass(OrderInterface::class);
        $this->orderManagement->method('place')
            ->with($firstOrder)
            ->willThrowException(new \Exception('place order error'));

        $this->placeOrder->place([$firstOrder]);
    }

    /**
     * Case when first order is placed successfully, but order payment doesn't contain vault payment token.
     *
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Vault Payment Token should be defined for placed order payment.
     * @return void
     */
    public function testFirstOrderWithoutPaymentToken()
    {
        $firstOrder = $this->getOrderMock('000000001', null);
        $this->orderManagement->method('place')
            ->with($firstOrder)
            ->willReturn($firstOrder);

        $this->placeOrder->place([$firstOrder]);
    }

    /**
     * Case when first order is placed successfully, but next orders fails
     *
     * @return void
     */
    public function testPlaceOtherOrdersWithErrors()
    {
        $firstIncrementId = '000000001';
        $secondIncrementId = '000000002';
        $thirdIncrementId = '000000003';

        /** @var  PaymentTokenInterface|\PHPUnit_Framework_MockObject_MockObject $paymentToken */
        $paymentToken = $this->getMockBuilder(PaymentTokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $firstOrder = $this->getOrderMock($firstIncrementId, $paymentToken);
        $secondOrder = $this->getOrderMock($secondIncrementId, $paymentToken);
        $thirdOrder = $this->getOrderMock($thirdIncrementId, $paymentToken);

        $exception = new \Exception('place order error');
        $this->orderManagement->expects($this->exactly(3))
            ->method('place')
            ->willReturnOnConsecutiveCalls(
                $firstOrder,
                $this->throwException($exception),
                $this->throwException($exception)
            );

        $errors = $this->placeOrder->place([$firstOrder, $secondOrder, $thirdOrder]);

        $this->assertEquals(
            [$secondIncrementId => $exception, $thirdIncrementId => $exception],
            $errors,
            'Service should return exceptions for second and third order placing'
        );
    }

    /**
     * @param string $incrementId
     * @param PaymentTokenInterface|null $paymentToken
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getOrderMock(string $incrementId, $paymentToken): \PHPUnit_Framework_MockObject_MockObject
    {
        $order = $this->getMockForAbstractClass(OrderInterface::class);
        $payment = $this->getMockForAbstractClass(OrderPaymentInterface::class);
        $extensionAttributes = $this->getMockBuilder(OrderPaymentExtensionInterface::class)
            ->setMethods(['getVaultPaymentToken', 'setVaultPaymentToken'])
            ->getMockForAbstractClass();
        $extensionAttributes->method('getVaultPaymentToken')->willReturn($paymentToken);
        $payment->method('getExtensionAttributes')->willReturn($extensionAttributes);
        $order->method('getPayment')->willReturn($payment);
        $order->method('getIncrementId')->willReturn($incrementId);

        return $order;
    }
}
