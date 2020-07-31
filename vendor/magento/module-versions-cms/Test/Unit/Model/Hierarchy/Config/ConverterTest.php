<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Model\Hierarchy\Config;

class ConverterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\VersionsCms\Model\Hierarchy\Config\Converter
     */
    protected $_model;

    protected function setUp()
    {
        $this->_model = new \Magento\VersionsCms\Model\Hierarchy\Config\Converter();
    }

    /**
     * @covers \Magento\VersionsCms\Model\Hierarchy\Config\Converter::convert
     */
    public function testConvert()
    {
        $basePath = realpath(__DIR__) . '/_files/';
        $path = $basePath . 'menu_hierarchy.xml';
        $domDocument = new \DOMDocument();
        $domDocument->load($path);
        $expectedData = include $basePath . 'menuHierarchy.php';
        $this->assertEquals($expectedData, $this->_model->convert($domDocument));
    }
}
