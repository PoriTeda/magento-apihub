<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Test\Unit\Block\Adminhtml\Report\View\Tab\Grid\Column\Renderer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\DataObject;

class TextTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Support\Block\Adminhtml\Report\View\Tab\Grid\Column\Renderer\Text
     */
    protected $columnRenderer;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Support\Model\Report\HtmlGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $htmlGeneratorMock;

    /**
     * @var \Magento\Backend\Block\Widget\Grid\Column|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $columnMock;

    protected function setUp()
    {
        $this->htmlGeneratorMock = $this->getMockBuilder(\Magento\Support\Model\Report\HtmlGenerator::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->columnMock = $this->getMockBuilder(\Magento\Backend\Block\Widget\Grid\Column::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGetter'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->columnRenderer = $this->objectManagerHelper->getObject(
            \Magento\Support\Block\Adminhtml\Report\View\Tab\Grid\Column\Renderer\Text::class,
            [
                'htmlGenerator' => $this->htmlGeneratorMock
            ]
        );
    }

    public function testGetValue()
    {
        $rawText = 'raw column text';
        $text = __($rawText);
        $row = new DataObject(['value' => $rawText]);
        $html = '<span class="cell-value-flag-yes">' . $text . '</span>';

        $this->columnRenderer->setColumn($this->columnMock);

        $this->columnMock->expects($this->any())
            ->method('getGetter')
            ->willReturn('getValue');
        $this->htmlGeneratorMock->expects($this->any())
            ->method('getGridCellHtml')
            ->with($text, $rawText)
            ->willReturn($html);

        $this->assertEquals($html, $this->columnRenderer->_getValue($row));
    }
}
