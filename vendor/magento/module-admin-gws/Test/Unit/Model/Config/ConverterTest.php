<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Test\Unit\Model\Config;

class ConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdminGws\Model\Config\Converter
     */
    protected $_model;

    /**
     * @var string
     */
    protected $_fixturePath;

    protected function setUp()
    {
        $this->_model = new \Magento\AdminGws\Model\Config\Converter();
        $this->_fixturePath = realpath(__DIR__) . '/_files/';
    }

    public function testConvert()
    {
        $dom = new \DOMDocument();
        $dom->load($this->_fixturePath . 'adminGws.xml');
        $actual = $this->_model->convert($dom);
        $expected = require $this->_fixturePath . 'adminGws.php';
        $this->assertEquals($expected, $actual);
    }
}
