<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PromotionPermissions\Test\Unit\Block\Adminhtml\Reminder\Edit;

use Magento\PromotionPermissions\Block\Adminhtml\Reminder\Edit\Plugin as ReminderEditPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\PromotionPermissions\Helper\Data as DataHelper;
use Magento\Reminder\Block\Adminhtml\Reminder\Edit as ReminderEditBlock;
use Magento\Backend\Block\Widget\Button\Item as ButtonItemWidget;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ReminderEditPlugin
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
     * @var ReminderEditBlock|\PHPUnit_Framework_MockObject_MockObject
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
        $this->subjectMock = $this->getMockBuilder(ReminderEditBlock::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->buttonItemWidgetMock = $this->getMockBuilder(ButtonItemWidget::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(
            ReminderEditPlugin::class,
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
            ->method('getCanAdminEditReminderRules')
            ->willReturn(true);

        $this->assertEquals(
            $result,
            $this->plugin->afterCanRender($this->subjectMock, $result, $this->buttonItemWidgetMock)
        );
    }

    public function testAfterCanRenderAllowedButton()
    {
        $this->dataHelperMock->expects(static::atLeastOnce())
            ->method('getCanAdminEditReminderRules')
            ->willReturn(false);
        $this->buttonItemWidgetMock->expects(static::atLeastOnce())
            ->method('getId')
            ->willReturn('back');

        $this->assertTrue($this->plugin->afterCanRender($this->subjectMock, true, $this->buttonItemWidgetMock));
    }

    public function testAfterCanRenderRestrictedButton()
    {
        $this->dataHelperMock->expects(static::atLeastOnce())
            ->method('getCanAdminEditReminderRules')
            ->willReturn(false);
        $this->buttonItemWidgetMock->expects(static::atLeastOnce())
            ->method('getId')
            ->willReturn('save');

        $this->assertFalse($this->plugin->afterCanRender($this->subjectMock, true, $this->buttonItemWidgetMock));
    }
}
