<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Block\Adminhtml\Report\Create;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class BackButtonTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Block\Adminhtml\Report\Create\BackButton
     */
    protected $backButton;

    /**
     * @var \Magento\Backend\Block\Widget\Context
     */
    protected $context;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Framework\UrlInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $urlBuilderMock;

    protected function setUp()
    {
        $this->urlBuilderMock = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->getMockForAbstractClass();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->context = $this->objectManagerHelper->getObject(
            \Magento\Backend\Block\Widget\Context::class,
            [
                'urlBuilder' => $this->urlBuilderMock
            ]
        );
        $this->backButton = $this->objectManagerHelper->getObject(
            \Magento\Support\Block\Adminhtml\Report\Create\BackButton::class,
            [
                'context' => $this->context
            ]
        );
    }

    public function testGetButtonData()
    {
        $url = '/back/url';
        $buttonData = [
            'label' => __('Back'),
            'on_click' => 'location.href = \'/back/url\';',
            'class' => 'back'
        ];

        $this->urlBuilderMock->expects($this->any())
            ->method('getUrl')
            ->with('*/*/', [])
            ->willReturn($url);

        $this->assertEquals($buttonData, $this->backButton->getButtonData());
    }
}
