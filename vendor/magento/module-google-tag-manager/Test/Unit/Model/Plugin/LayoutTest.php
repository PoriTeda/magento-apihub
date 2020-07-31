<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Test\Unit\Model\Plugin;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class LayoutTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GoogleTagManager\Model\Plugin\Layout */
    protected $layout;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \Magento\GoogleTagManager\Helper\Data|\PHPUnit_Framework_MockObject_MockObject */
    protected $helper;

    protected function setUp()
    {
        $this->helper = $this->createMock(\Magento\GoogleTagManager\Helper\Data::class);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->layout = $this->objectManagerHelper->getObject(
            \Magento\GoogleTagManager\Model\Plugin\Layout::class,
            [
                'helper' => $this->helper
            ]
        );
    }

    /**
     * @param bool $available
     * @param mixed $expectsBanner
     * @param mixed $expects
     *
     * @dataProvider afterCreateBlockDataProvider
     */
    public function testAfterCreateBlock($available, $expectsBanner, $expects)
    {
        $result = $this->createMock(\Magento\Banner\Block\Widget\Banner::class);

        $block = $this->createMock(\Magento\GoogleTagManager\Block\ListJson::class);
        $block->expects($expectsBanner)->method('appendBannerBlock')->with($result);

        $subject = $this->createMock(\Magento\Framework\View\LayoutInterface::class);
        $subject->expects($expects)->method('getBlock')->with('banner_impression')->willReturn($block);

        $this->helper->expects($this->atLeastOnce())->method('isTagManagerAvailable')->willReturn($available);

        $this->assertSame($result, $this->layout->afterCreateBlock($subject, $result));
    }

    public function afterCreateBlockDataProvider()
    {
        return [
            [true, $this->once(), $this->once()],
            [false, $this->never(), $this->never()]
        ];
    }

    public function testAfterCreateBlockForNonBanners()
    {
        $result = $this->createMock(\Magento\Framework\View\Element\BlockInterface::class);

        $subject = $this->createMock(\Magento\Framework\View\LayoutInterface::class);
        $subject->expects($this->never())->method('getBlock');

        $this->helper->expects($this->atLeastOnce())->method('isTagManagerAvailable')->willReturn(true);

        $this->assertSame($result, $this->layout->afterCreateBlock($subject, $result));
    }
}
