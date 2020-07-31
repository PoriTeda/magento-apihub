<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Test\Unit\Block\Adminhtml\Banner\Edit\Tab\Promotions;

class CatalogruleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Banner\Block\Adminhtml\Banner\Edit\Tab\Promotions\Catalogrule
     */
    protected $catalogRule;

    /**
     * @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Backend\Block\Template\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $context;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    protected function setUp()
    {
        $this->urlBuilder = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fileSystem = $this->getMockBuilder(\Magento\Framework\Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context = $this->getMockBuilder(\Magento\Backend\Block\Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registry = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->context->expects($this->once())->method('getFilesystem')->will($this->returnValue($fileSystem));

        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->context->expects($this->once())->method('getUrlBuilder')->will($this->returnValue($this->urlBuilder));
        $this->catalogRule = $objectManagerHelper->getObject(
            \Magento\Banner\Block\Adminhtml\Banner\Edit\Tab\Promotions\Catalogrule::class,
            [
                'context' => $this->context,
                'registry' => $this->registry
            ]
        );
    }

    public function testGetTabLabel()
    {
        $this->urlBuilder->expects($this->once())->method('getUrl')->with(
            'adminhtml/*/catalogRuleGrid',
            ['_current' => true]
        )->will($this->returnValue('test_string'));

        $this->assertEquals('test_string', $this->catalogRule->getGridUrl());
    }

    public function testGetRelatedCatalogRule()
    {
        $banner = $this->getMockBuilder(\Magento\Banner\Model\Banner::class)
            ->disableOriginalConstructor()
            ->getMock();
        $banner->expects($this->once())->method('getRelatedCatalogRule')->will($this->returnValue(['test1', 'test2']));
        $this->registry->expects($this->once())->method('registry')->with('current_banner')->will(
            $this->returnValue($banner)
        );
        $this->assertEquals(['test1', 'test2'], $this->catalogRule->getRelatedCatalogRule());
    }
}
