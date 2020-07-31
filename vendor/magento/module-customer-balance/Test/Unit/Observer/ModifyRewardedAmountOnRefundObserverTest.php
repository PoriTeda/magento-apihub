<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerBalance\Test\Unit\Observer;

/**
 * Class ModifyRewardedAmountOnRefundObserverTest
 */
class ModifyRewardedAmountOnRefundObserverTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\CustomerBalance\Observer\ModifyRewardedAmountOnRefundObserver */
    protected $model;

    /**
     * @var \Magento\Framework\Event\Observer
     */
    protected $observer;

    /**
     * @var \Magento\Framework\DataObject
     */
    protected $event;

    protected function setUp()
    {
        $this->event = new \Magento\Framework\DataObject();
        $this->observer = new \Magento\Framework\Event\Observer(['event' => $this->event]);

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->model = $objectManagerHelper->getObject(
            \Magento\CustomerBalance\Observer\ModifyRewardedAmountOnRefundObserver::class
        );
    }

    /**
     * @param array $orderData
     * @param integer $baseRewardAmount
     * @param integer $expectedRewardAmount
     *
     * @dataProvider modifyRewardedAmountOnRefundDataProvider
     */
    public function testModifyRewardedAmountOnRefund($orderData, $baseRewardAmount, $expectedRewardAmount)
    {
        $orderMock = $this->createPartialMock(\Magento\Sales\Model\Order::class, [
                'getBsCustomerBalTotalRefunded',
                'getBaseTotalRefunded',
                'getBaseTaxRefunded',
                'getBaseShippingRefunded',
                '__wakeup',
                '__sleep'
            ]);
        $orderMock->expects($this->any())->method('getBsCustomerBalTotalRefunded')
            ->will($this->returnValue($orderData['bs_customer_bal_total_refunded']));
        $orderMock->expects($this->any())->method('getBaseTotalRefunded')
            ->will($this->returnValue($orderData['base_total_refunded']));
        $orderMock->expects($this->any())->method('getBaseTaxRefunded')
            ->will($this->returnValue($orderData['base_tax_refunded']));
        $orderMock->expects($this->any())->method('getBaseShippingRefunded')
            ->will($this->returnValue($orderData['base_shipping_refunded']));

        $creditMemoMock = $this->createPartialMock(
            \Magento\Sales\Model\Order\Creditmemo::class,
            ['getRewardedAmountAfterRefund', 'setRewardedAmountAfterRefund', 'getOrder', '__wakeup', '__sleep']
        );
        $creditMemoMock->expects($this->any())->method('getOrder')
            ->will($this->returnValue($orderMock));
        $creditMemoMock->expects($this->any())->method('getRewardedAmountAfterRefund')
            ->will($this->returnValue($baseRewardAmount));

        $creditMemoMock->expects($this->once())->method('setRewardedAmountAfterRefund')->with($expectedRewardAmount);
        $this->event->setCreditmemo($creditMemoMock);

        $this->model->execute($this->observer);
    }

    /**
     * @return array
     */
    public function modifyRewardedAmountOnRefundDataProvider()
    {
        return [
            [
                'orderData' => [
                    'bs_customer_bal_total_refunded' => 100,
                    'base_total_refunded' => 40,
                    'base_tax_refunded' => 10,
                    'base_shipping_refunded' => 10,
                ],
                'baseRewardAmount' => 5,
                'expectedRewardAmount' => 25,
            ],
            [
                'orderData' => [
                    'bs_customer_bal_total_refunded' => 10,
                    'base_total_refunded' => 40,
                    'base_tax_refunded' => 10,
                    'base_shipping_refunded' => 10,
                ],
                'baseRewardAmount' => 10,
                'expectedRewardAmount' => 20
            ]
        ];
    }
}
