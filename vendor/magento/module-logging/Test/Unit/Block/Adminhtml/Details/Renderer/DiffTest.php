<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\Test\Unit\Block\Adminhtml\Details\Renderer;

/**
 * @magentoAppArea adminhtml
 */
class DiffTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Diff
     */
    protected $_object;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $_column;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $json;

    protected function setUp()
    {
        $escaper = $this->createMock(\Magento\Framework\Escaper::class);
        $escaper->expects($this->any())->method('escapeHtml')->will($this->returnArgument(0));
        $context = $this->createMock(\Magento\Backend\Block\Context::class);
        $context->expects($this->once())
            ->method('getEscaper')
            ->will($this->returnValue($escaper));

        $this->json = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_column = $this->createPartialMock(
            \Magento\Backend\Block\Widget\Grid\Column\Extended::class,
            ['getValues', 'getIndex', 'getHtmlName']
        );

        $this->_object = new \Magento\Logging\Block\Adminhtml\Details\Renderer\Diff($context, [], $this->json);
        $this->_object->setColumn($this->_column);
    }

    /**
     * @param array $rowData
     * @param string $expectedResult
     * @dataProvider renderDataProvider
     */
    public function testRender($rowData, $expectedResult)
    {
        $this->json->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->_column->expects($this->once())->method('getIndex')->will($this->returnValue('result_data'));
        $this->assertContains($expectedResult, $this->_object->render(new \Magento\Framework\DataObject($rowData)));
    }

    public function renderDataProvider()
    {
        return [
            'allowed' => [
                ['result_data' => '{"allow":["TMM","USD"]}'],
                '<dd class="value">TMM</dd><dd class="value">USD</dd>',
            ],
            'time' => [
                ['result_data' => '{"time":["00","00","00"]}'],
                '<dd class="value">00:00:00</dd>',
            ]
        ];
    }
}
