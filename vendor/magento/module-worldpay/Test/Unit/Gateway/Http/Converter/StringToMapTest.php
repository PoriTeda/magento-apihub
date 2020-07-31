<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Worldpay\Test\Unit\Gateway\Http\Converter;

use Magento\Worldpay\Gateway\Http\Converter\StringToMap;

class StringToMapTest extends \PHPUnit\Framework\TestCase
{
    public function testConvertException()
    {
        $this->expectException(\Magento\Payment\Gateway\Http\ConverterException::class);
        $this->expectExceptionMessage((string)__('The response type is incorrect. Verify the type and try again.'));

        $converter = new StringToMap();
        $converter->convert([]);
    }

    public function testConvertSuccess()
    {
        $converter = new StringToMap();
        static::assertEquals(
            ['Work hard', 'die tired'],
            $converter->convert('Work hard,die tired')
        );
    }
}
