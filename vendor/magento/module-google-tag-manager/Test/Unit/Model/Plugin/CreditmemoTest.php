<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Test\Unit\Model\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class CreditmemoTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GoogleTagManager\Model\Plugin\Creditmemo */
    protected $creditmemo;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \Magento\GoogleTagManager\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $helper;

    /** @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    protected function setUp()
    {
        $this->helper = $this->createMock(\Magento\GoogleTagManager\Helper\Data::class);
        $this->session = $this->createPartialMock(\Magento\Backend\Model\Session::class, ['setData']);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->creditmemo = $this->objectManagerHelper->getObject(
            \Magento\GoogleTagManager\Model\Plugin\Creditmemo::class,
            [
                'helper' => $this->helper,
                'backendSession' => $this->session
            ]
        );
    }

    public function testAfterSave()
    {
        $this->helper->expects($this->atLeastOnce())->method('isTagManagerAvailable')->willReturn(true);

        $this->session->expects($this->any())
            ->method('setData')
            ->withConsecutive(
                [
                    'googleanalytics_creditmemo_order',
                    '00000001'
                ],
                [
                    'googleanalytics_creditmemo_store_id',
                    2
                ],
                [
                    'googleanalytics_creditmemo_revenue',
                    '19.99'
                ],
                [
                    'googleanalytics_creditmemo_products',
                    [
                        [
                            'id' => 'Item 1',
                            'quantity' => 3
                        ]
                    ]
                ]
            )
            ->willReturnSelf();

        $order = $this->createMock(\Magento\Sales\Model\Order::class);
        $order->expects($this->any())->method('getIncrementId')->willReturn('00000001');
        $order->expects($this->any())->method('getBaseGrandTotal')->willReturn('29.99');

        $item1 = $this->createMock(\Magento\Sales\Model\Order\Creditmemo\Item::class);
        $item1->expects($this->any())->method('getQty')->willReturn(3);
        $item1->expects($this->any())->method('getSku')->willReturn('Item 1');

        $item2 = $this->createMock(\Magento\Sales\Model\Order\Creditmemo\Item::class);
        $item2->expects($this->any())->method('getQty')->willReturn(0);
        $item2->expects($this->any())->method('getSku')->willReturn('Item 2');

        $collection = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Creditmemo\Item\Collection::class);
        $collection->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$item1, $item2]));

        /** @var \Magento\Sales\Model\Order\Creditmemo|\PHPUnit_Framework_MockObject_MockObject $result */
        $result = $this->createMock(\Magento\Sales\Model\Order\Creditmemo::class);
        $result->expects($this->any())->method('getOrder')->willReturn($order);
        $result->expects($this->any())->method('getStoreId')->willReturn(2);
        $result->expects($this->any())->method('getBaseGrandTotal')->willReturn('19.99');
        $result->expects($this->any())->method('getItemsCollection')->willReturn($collection);

        $this->assertSame($result, $this->creditmemo->afterSave($result, $result));
    }

    public function testAfterSaveNotAvailable()
    {
        $this->helper->expects($this->atLeastOnce())->method('isTagManagerAvailable')->willReturn(false);
        /** @var \Magento\Sales\Model\Order\Creditmemo|\PHPUnit_Framework_MockObject_MockObject $result */
        $result = $this->createMock(\Magento\Sales\Model\Order\Creditmemo::class);
        $result->expects($this->never())->method('getOrder');
        $this->session->expects($this->never())->method('setData');

        $this->assertSame($result, $this->creditmemo->afterSave($result, $result));
    }
}
