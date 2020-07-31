<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Test\Unit\Block\Product\View;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Covered afterGetWishlistOptions
     *
     * @test
     */
    public function testAfterGetWishlistOptions()
    {
        $expected = ['key1' => 'value1', 'giftcardInfo' => '[id^=giftcard]'];
        $param = ['key1' => 'value1'];
        $block = $this->getMockBuilder(
            \Magento\Catalog\Block\Product\View::class
        )->disableOriginalConstructor()->getMock();
        /** @var $block \Magento\Catalog\Block\Product\View */
        $this->assertEquals(
            $expected,
            (new \Magento\GiftCard\Block\Product\View\Plugin())->afterGetWishlistOptions($block, $param)
        );
    }
}
