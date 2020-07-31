<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PromotionPermissions\Test\Unit\Block\Adminhtml\Promo\Quote;

use Magento\PromotionPermissions\Block\Adminhtml\Promo\Quote\Plugin as QuotePlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\PromotionPermissions\Helper\Data as DataHelper;
use Magento\SalesRule\Block\Adminhtml\Promo\Quote as PromoQuoteBlock;
use Magento\Backend\Block\Widget\Button\Item as ButtonItemWidget;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var QuotePlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var DataHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $dataHelperMock;

    /**
     * @var PromoQuoteBlock|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    /**
     * @var ButtonItemWidget|\PHPUnit_Framework_MockObject_MockObject
     */
    private $buttonItemWidgetMock;

    protected function setUp()
    {
        $this->dataHelperMock = $this->getMockBuilder(DataHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectMock = $this->getMockBuilder(PromoQuoteBlock::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->buttonItemWidgetMock = $this->getMockBuilder(ButtonItemWidget::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(
            QuotePlugin::class,
            ['dataHelper' => $this->dataHelperMock]
        );
    }

    public function testAfterCanRenderNegativeResult()
    {
        $result = false;

        $this->assertEquals(
            $result,
            $this->plugin->afterCanRender($this->subjectMock, $result, $this->buttonItemWidgetMock)
        );
    }

    public function testAfterCanRenderCanEdit()
    {
        $result = true;

        $this->dataHelperMock->expects(static::atLeastOnce())
            ->method('getCanAdminEditSalesRules')
            ->willReturn(true);

        $this->assertEquals(
            $result,
            $this->plugin->afterCanRender($this->subjectMock, $result, $this->buttonItemWidgetMock)
        );
    }

    public function testAfterCanRenderAllowedButton()
    {
        $this->dataHelperMock->expects(static::atLeastOnce())
            ->method('getCanAdminEditSalesRules')
            ->willReturn(false);
        $this->buttonItemWidgetMock->expects(static::atLeastOnce())
            ->method('getId')
            ->willReturn('back');

        $this->assertTrue($this->plugin->afterCanRender($this->subjectMock, true, $this->buttonItemWidgetMock));
    }

    public function testAfterCanRenderRestrictedButton()
    {
        $this->dataHelperMock->expects(static::atLeastOnce())
            ->method('getCanAdminEditSalesRules')
            ->willReturn(false);
        $this->buttonItemWidgetMock->expects(static::atLeastOnce())
            ->method('getId')
            ->willReturn('add');

        $this->assertFalse($this->plugin->afterCanRender($this->subjectMock, true, $this->buttonItemWidgetMock));
    }
}
