<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Test\Unit\Model\Backend;

/**
 * Test class for \Magento\AdvancedCheckout\Model\Backend\Cart
 */
class CartTest extends \PHPUnit\Framework\TestCase
{
    public function testGetActualQuote()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $quote = $this->createPartialMock(\Magento\Quote\Model\Quote::class, ['getQuote', '__wakeup']);
        $quote->expects($this->once())->method('getQuote')->will($this->returnValue('some value'));
        /** @var Cart $model */
        $model = $helper->getObject(\Magento\AdvancedCheckout\Model\Backend\Cart::class);
        $model->setQuote($quote);
        $this->assertEquals('some value', $model->getActualQuote());
    }
}
