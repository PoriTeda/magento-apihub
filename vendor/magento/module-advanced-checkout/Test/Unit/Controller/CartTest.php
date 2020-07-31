<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Test\Unit\Controller;

class CartTest extends \PHPUnit\Framework\TestCase
{
    public function testControllerImplementsProductViewInterface()
    {
        $this->assertInstanceOf(
            \Magento\Catalog\Controller\Product\View\ViewInterface::class,
            $this->createMock(\Magento\AdvancedCheckout\Controller\Cart::class)
        );
    }
}
