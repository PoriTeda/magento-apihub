<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Model\Item;

class OptionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param mixed $product
     * @param mixed $expectedProduct
     * @param int $expectedProductId
     * @dataProvider setProductDataProvider
     */
    public function testSetProduct($product, $expectedProduct, $expectedProductId)
    {
        $model = $this->createPartialMock(\Magento\GiftRegistry\Model\Item\Option::class, ['getValue', '__wakeup']);
        $model->setProduct($product);

        $this->assertEquals($expectedProduct, $model->getProduct());
        $this->assertEquals($expectedProductId, $model->getProductId());
    }

    public function setProductDataProvider()
    {
        $product = $this->createPartialMock(\Magento\Catalog\Model\Product::class, ['getId', '__sleep', '__wakeup']);
        $product->expects($this->any())->method('getId')->will($this->returnValue(3));
        return [[$product, $product, 3], [null, null, null]];
    }
}
