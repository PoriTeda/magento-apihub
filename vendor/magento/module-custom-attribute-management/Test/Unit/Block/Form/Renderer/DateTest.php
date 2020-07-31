<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomAttributeManagement\Test\Unit\Block\Form\Renderer;

class DateTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tested block
     *
     * @var \Magento\CustomAttributeManagement\Block\Form\Renderer\Date
     */
    protected $_block;

    /**
     * Locale date mock
     *
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_localeDateMock;

    /**
     * Date element mock
     *
     * @var \Magento\Framework\View\Element\Html\Date|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $dateElement;

    /**
     * Request mock
     *
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $request;

    /**
     * Asste repository mock
     *
     * @var \Magento\Framework\View\Asset\Repository|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $assetRepo;

    protected function setUp()
    {
        $contextMock = $this->createMock(\Magento\Framework\View\Element\Template\Context::class);

        $this->_localeDateMock = $this->getMockForAbstractClass(
            \Magento\Framework\Stdlib\DateTime\TimezoneInterface::class
        );

        $contextMock->expects($this->once())
            ->method('getLocaleDate')
            ->will($this->returnValue($this->_localeDateMock));

        $this->request = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()->getMock();

        $this->assetRepo = $this->getMockBuilder(\Magento\Framework\View\Asset\Repository::class)
            ->disableOriginalConstructor()->getMock();

        $contextMock->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);

        $contextMock->expects($this->any())
            ->method('getAssetRepository')
            ->willReturn($this->assetRepo);

        $this->dateElement = $this->getMockBuilder(\Magento\Framework\View\Element\Html\Date::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_block = new \Magento\CustomAttributeManagement\Block\Form\Renderer\Date(
            $contextMock,
            $this->dateElement
        );
    }

    public function testGetFieldHtml()
    {
        $testResult = '<input type="date" value="">';

        $this->request->expects($this->any())
            ->method('isSecure')
            ->willReturn(false);

        $this->dateElement->expects($this->once())
            ->method('setData')
            ->willReturnSelf();
        $this->dateElement->expects($this->once())
            ->method('getHtml')
            ->willReturn($testResult);

        $this->_block->setAttributeObject(
            $this->getMockBuilder(\Magento\Eav\Model\Attribute::class)->disableOriginalConstructor()->getMock()
        );
        $this->_block->setEntity(
            $this->getMockBuilder(
                \Magento\Framework\Model\AbstractModel::class
            )->disableOriginalConstructor()->getMock()
        );
        $this->assertEquals($testResult, $this->_block->getFieldHtml());
    }

    public function testGetDateFormat()
    {
        $this->_localeDateMock->expects($this->once())
            ->method('getDateFormat')
            ->with(\IntlDateFormatter::SHORT)
            ->will($this->returnArgument(0));

        $this->assertEquals(
            \IntlDateFormatter::SHORT,
            $this->_block->getDateFormat()
        );
    }

    /**
     * Test for stored date inputs getter
     *
     * @param string $expected
     * @param array $data
     * @dataProvider getSortedDateInputsDataProvider
     */
    public function testGetSortedDateInputs($expected, array $data)
    {
        $this->_localeDateMock->expects($this->once())
            ->method('getDateFormat')
            ->with(\IntlDateFormatter::SHORT)
            ->will($this->returnValue($data['format']));

        foreach ($data['date_inputs'] as $code => $html) {
            $this->_block->setDateInput($code, $html);
        }
        $this->assertEquals($expected, $this->_block->getSortedDateInputs($data['strip_non_input_chars']));
    }

    /**
     * @return array
     */
    public function getSortedDateInputsDataProvider()
    {
        return [
            [
                '<y><d><d><m>',
                [
                    'strip_non_input_chars' => true,
                    'date_inputs' => [
                        'm' => '<m>',
                        'd' => '<d>',
                        'y' => '<y>',
                    ],
                    'format' => 'y--d--e--m'
                ],
            ],
            [
                '<y>--<d>--<d>--<m>',
                [
                    'strip_non_input_chars' => false,
                    'date_inputs' => [
                        'm' => '<m>',
                        'd' => '<d>',
                        'y' => '<y>',
                    ],
                    'format' => 'y--d--e--m'
                ]
            ],

            [
                '<m><d><d><y>',
                [
                    'strip_non_input_chars' => true,
                    'date_inputs' => [
                        'm' => '<m>',
                        'd' => '<d>',
                        'y' => '<y>',
                    ],
                    'format' => '[medy]'
                ]
            ],
            [
                '[<m><d><d><y>]',
                [
                    'strip_non_input_chars' => false,
                    'date_inputs' => [
                        'm' => '<m>',
                        'd' => '<d>',
                        'y' => '<y>',
                    ],
                    'format' => '[medy]'
                ]
            ]
        ];
    }
}
