<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Test\Unit\Block\Adminhtml\Creditmemo;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class GaTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GoogleTagManager\Block\Adminhtml\Creditmemo\Ga */
    protected $ga;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \Magento\Backend\Model\Session|\PHPUnit_Framework_MockObject_MockObject */
    protected $session;

    protected function setUp()
    {
        $this->session = $this->createMock(\Magento\Backend\Model\Session::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->ga = $this->objectManagerHelper->getObject(
            \Magento\GoogleTagManager\Block\Adminhtml\Creditmemo\Ga::class,
            [
                'backendSession' => $this->session
            ]
        );
    }

    /**
     * @param int|null $orderId
     * @param int|string $expected
     *
     * @dataProvider getOrderIdDataProvider
     */
    public function testGetOrderId($orderId, $expected)
    {
        $this->session->expects($this->any())->method('getData')->with('googleanalytics_creditmemo_order', true)
            ->willReturn($orderId);
        $this->assertEquals($expected, $this->ga->getOrderId());
    }

    public function getOrderIdDataProvider()
    {
        return [
            [10, 10],
            [null, '']
        ];
    }

    /**
     * @param int|null $revenue
     * @param int|string $expected
     *
     * @dataProvider getRevenueDataProvider
     */
    public function testGetRevenue($revenue, $expected)
    {
        $this->session->expects($this->any())->method('getData')->with('googleanalytics_creditmemo_revenue', true)
            ->willReturn($revenue);
        $this->assertEquals($expected, $this->ga->getRevenue());
    }

    public function getRevenueDataProvider()
    {
        return [
            [101, 101],
            [null, '']
        ];
    }

    /**
     * @param int|null $products
     * @param int|string $expected
     *
     * @dataProvider getProductsDataProvider
     */
    public function testGetProducts($products, $expected)
    {
        $this->session->expects($this->any())->method('getData')->with('googleanalytics_creditmemo_products', true)
            ->willReturn($products);
        $this->assertEquals($expected, $this->ga->getProducts());
    }

    public function getProductsDataProvider()
    {
        return [
            [[1,2,3], [1,2,3]],
            [null, []]
        ];
    }

    public function testGetRefundJson()
    {
        $this->session->expects($this->any())->method('getData')->willReturnMap([
            ['googleanalytics_creditmemo_order', true, 11],
            ['googleanalytics_creditmemo_revenue', true, 22],
            ['googleanalytics_creditmemo_products', true, [31, 32]],
        ]);
        $this->assertEquals(
            '{"event":"refund","ecommerce":{"refund":{"actionField":{"id":11,"revenue":22},"products":[31,32]}}}',
            $this->ga->getRefundJson()
        );
    }
}
