<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Eway\Test\Unit\Gateway\Http\Converter;

use Magento\Eway\Gateway\Http\Converter\JsonToArray;

class JsonToArrayTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var JsonToArray
     */
    private $converter;

    protected function setUp()
    {
        $this->converter = new JsonToArray();
    }

    /**
     * @expectedException \Magento\Payment\Gateway\Http\ConverterException
     * @expectedExceptionMessage The response type is incorrect. Verify the type and try again.
     */
    public function testConvertException()
    {
        $this->converter->convert(['key' => 'value']);
    }

    /**
     * @param string $string
     * @param array $expectedMap
     * @dataProvider convertDataProvider
     */
    public function testConvert($string, $expectedMap)
    {
        $this->assertEquals($expectedMap, $this->converter->convert($string));
    }

    /**
     * 1) simple array
     * 2) associative array
     * 3) nested associative array
     *
     * @return array
     */
    public function convertDataProvider()
    {
        return [
            [
                '["data"]', ['data']
            ],
            [
                '{"key":"value"}', ['key' => 'value']
            ],
            [
                '{"key1":{"key2":"value"}}', ['key1' => ['key2' => 'value']]
            ]
        ];
    }
}
