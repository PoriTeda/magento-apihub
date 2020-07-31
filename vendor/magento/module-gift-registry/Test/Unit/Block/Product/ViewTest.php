<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Block\Product;

class ViewTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftRegistry\Block\Product\View|null
     */
    protected $_block = null;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|null
     */
    protected $_urlBuilder = null;

    protected function setUp()
    {
        $helper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->_urlBuilder = $this->getMockForAbstractClass(\Magento\Framework\UrlInterface::class);
        $args = ['urlBuilder' => $this->_urlBuilder];
        $this->_block = $helper->getObject(\Magento\GiftRegistry\Block\Product\View::class, $args);
    }

    /**
     * @param string $options
     * @param string|null $expectedTemplate
     * @dataProvider setGiftRegistryTemplateDataProvider
     */
    public function testSetGiftRegistryTemplate($options, $expectedTemplate)
    {
        $request = $this->_block->getRequest();
        $request->expects($this->any())->method('getParam')->with('options')->will($this->returnValue($options));
        $childBlock = $this->getMockForAbstractClass(
            \Magento\Framework\View\Element\AbstractBlock::class,
            [],
            '',
            false
        );
        $layout = $this->createMock(\Magento\Framework\View\Layout::class);
        $this->_block->setLayout($layout);
        $layout->expects($this->once())->method('getBlock')->with('test')->will($this->returnValue($childBlock));
        $this->_block->setGiftRegistryTemplate('test', 'template.phtml');
        $actualTemplate = $childBlock->getTemplate();
        $this->assertSame($expectedTemplate, $actualTemplate);
    }

    /**
     * @return array
     */
    public function setGiftRegistryTemplateDataProvider()
    {
        return [
            'no options' => ['some other option', null],
            'with options' => [\Magento\GiftRegistry\Block\Product\View::FLAG, 'template.phtml']
        ];
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Could not find block 'test'
     */
    public function testSetGiftRegistryTemplateNoBlock()
    {
        $this->_block->setGiftRegistryTemplate('test', 'template.phtml');
    }

    public function testSetGiftRegistryUrl()
    {
        $this->_urlBuilder->expects($this->any())->method('getUrl')->will($this->returnValue('some_url'));
        $request = $this->_block->getRequest();
        $valueMap = [
            ['options', null, \Magento\GiftRegistry\Block\Product\View::FLAG],
            ['entity', null, 'any'],
        ];
        $request->expects($this->any())->method('getParam')->will($this->returnValueMap($valueMap));
        $childBlock = $this->getMockForAbstractClass(
            \Magento\Framework\View\Element\AbstractBlock::class,
            [],
            '',
            false
        );
        $layout = $this->createMock(\Magento\Framework\View\Layout::class);
        $this->_block->setLayout($layout);
        $layout->expects($this->once())->method('getBlock')->with('test')->will($this->returnValue($childBlock));
        $this->_block->setGiftRegistryUrl('test');
        $actualUrl = $childBlock->getAddToGiftregistryUrl();
        $this->assertSame('some_url', $actualUrl);
    }

    public function testSetGiftRegistryUrlNoOptions()
    {
        $childBlock = $this->getMockForAbstractClass(
            \Magento\Framework\View\Element\AbstractBlock::class,
            [],
            '',
            false
        );
        $layout = $this->createMock(\Magento\Framework\View\Layout::class);
        $this->_block->setLayout($layout);
        $layout->expects($this->once())->method('getBlock')->with('test')->will($this->returnValue($childBlock));
        $this->_block->setGiftRegistryUrl('test');
        $actualUrl = $childBlock->getGiftRegistryUrl();
        $this->assertNull($actualUrl);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Could not find block 'test'
     */
    public function testSetGiftRegistryUrlNoBlock()
    {
        $this->_block->setGiftRegistryUrl('test');
    }
}
