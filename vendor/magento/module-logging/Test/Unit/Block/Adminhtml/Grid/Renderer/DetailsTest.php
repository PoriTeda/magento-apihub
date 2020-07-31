<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Test\Unit\Block\Adminhtml\Grid\Renderer;

class DetailsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Logging\Block\Adminhtml\Grid\Renderer\Details
     */
    protected $object;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonMock;

    protected function setUp()
    {
        $escaper = $this->createMock(\Magento\Framework\Escaper::class);
        $escaper->expects($this->any())->method('escapeHtml')->will($this->returnArgument(0));
        $contextMock = $this->getMockBuilder(\Magento\Backend\Block\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->method('getEscaper')
            ->willReturn($escaper);

        $this->jsonMock = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->object = $this->objectManager->getObject(
            \Magento\Logging\Block\Adminhtml\Grid\Renderer\Details::class,
            [
                'context' => $contextMock,
                'data' => [],
                'json' => $this->jsonMock
            ]
        );
    }

    /**
     * @param array $data
     * @param string $expectedResult
     * @dataProvider renderDataProvider
     */
    public function testRender($data, $expectedResult)
    {
        $row = $this->getMockBuilder(\Magento\Framework\DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $row->method('getData')->willReturn($data);
        $column = $this->getMockBuilder(\Magento\Backend\Block\Widget\Grid\Column::class)
            ->setMethods(['getIndex'])
            ->disableOriginalConstructor()
            ->getMock();
        $column->method('getIndex')->willReturn($row);

        $this->object->setColumn($column);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->assertEquals($expectedResult, $this->object->render($row));
    }

    public function renderDataProvider()
    {
        return [
            'set1' => [
                'true',
                'true',
            ],
            'set2' => [
                '{"general": ["some parsed value"]}',
                'some parsed value',
            ]
        ];
    }
}
