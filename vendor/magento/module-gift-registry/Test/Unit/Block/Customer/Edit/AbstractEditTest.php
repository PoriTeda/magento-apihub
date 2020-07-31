<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Test\Unit\Block\Customer\Edit;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AbstractEditTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeDateMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $layoutMock;

    /**
     * @var \Magento\GiftRegistry\Block\Customer\Edit\AbstractEdit
     */
    protected $block;

    protected function setUp()
    {
        $this->contextMock = $this->createMock(\Magento\Framework\View\Element\Template\Context::class);
        $this->localeDateMock = $this->createMock(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);
        $this->layoutMock = $this->createMock(\Magento\Framework\View\LayoutInterface::class);
        $this->contextMock->expects($this->any())->method('getLayout')->will($this->returnValue($this->layoutMock));
        $this->contextMock
            ->expects($this->any())
            ->method('getLocaleDate')
            ->will($this->returnValue($this->localeDateMock));
        $requestMock = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()->getMock();
        $this->contextMock->expects($this->any())->method('getRequest')->will($this->returnValue($requestMock));
        $assertRepoMock = $this->createMock(\Magento\Framework\View\Asset\Repository::class);
        $this->contextMock
            ->expects($this->once())
            ->method('getAssetRepository')
            ->will($this->returnValue($assertRepoMock));

        $assertRepoMock->expects($this->once())->method('getUrlWithParams');
        $this->block = $this->getMockForAbstractClass(
            \Magento\GiftRegistry\Block\Customer\Edit\AbstractEdit::class,
            [
                $this->contextMock,
                $this->createMock(\Magento\Directory\Helper\Data::class),
                $this->createMock(\Magento\Framework\Json\EncoderInterface::class),
                $this->createMock(\Magento\Framework\App\Cache\Type\Config::class),
                $this->createPartialMock(
                    \Magento\Directory\Model\ResourceModel\Region\CollectionFactory::class,
                    ['create']
                ),
                $this->createPartialMock(
                    \Magento\Directory\Model\ResourceModel\Country\CollectionFactory::class,
                    ['create']
                ),
                $this->createMock(\Magento\Framework\Registry::class),
                $this->createMock(\Magento\Customer\Model\Session::class),
                $this->createMock(\Magento\GiftRegistry\Model\Attribute\Config::class),
                []
            ]
        );
    }

    public function testGetCalendarDateHtml()
    {
        $value = '07/24/14';
        $dateTime = new \DateTime($value);
        $methods = ['setId', 'setName', 'setValue', 'setClass', 'setImage', 'setDateFormat', 'getHtml'];
        $block = $this->createPartialMock(\Magento\GiftRegistry\Block\Customer\Date::class, $methods);
        $this->localeDateMock
            ->expects($this->once())
            ->method('formatDateTime')
            ->with($dateTime, \IntlDateFormatter::MEDIUM, \IntlDateFormatter::NONE)
            ->will($this->returnValue($value));
        $this->localeDateMock
            ->expects($this->once())
            ->method('getDateFormat')
            ->with(\IntlDateFormatter::MEDIUM)
            ->will($this->returnValue('format'));
        $this->layoutMock->expects($this->once())
            ->method('createBlock')
            ->with(\Magento\GiftRegistry\Block\Customer\Date::class)->will($this->returnValue($block));
        $block->expects($this->once())->method('setId')->with('id')->will($this->returnSelf());
        $block->expects($this->once())->method('setName')->with('name')->will($this->returnSelf());
        $block->expects($this->once())->method('setValue')->with($value)->will($this->returnSelf());
        $block->expects($this->once())
            ->method('setClass')
            ->with(' product-custom-option datetime-picker input-text validate-date')
            ->will($this->returnSelf());
        $block->expects($this->once())
            ->method('setImage')
            ->will($this->returnSelf());
        $block->expects($this->once())
            ->method('setDateFormat')
            ->with('format')
            ->will($this->returnSelf());
        $block->expects($this->once())->method('getHtml')->will($this->returnValue('expected_html'));
        $this->assertEquals('expected_html', $this->block->getCalendarDateHtml('name', 'id', $value));
    }
}
