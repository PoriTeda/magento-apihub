<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Test\Unit\Helper\Catalog\Product;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Class ConfigurationTest
 */
class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /**
     * @var \Magento\GiftCard\Helper\Catalog\Product\Configuration
     */
    protected $helper;

    /**
     * @var \Magento\Catalog\Helper\Product\Configuration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $ctlgProdConfigur;

    /**
     * @var \Magento\Framework\Escaper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $escaper;

    protected function setUp()
    {
        $this->ctlgProdConfigur = $this->getMockBuilder(\Magento\Catalog\Helper\Product\Configuration::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->escaper = $this->getMockBuilder(\Magento\Framework\Escaper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->helper = $this->objectManagerHelper->getObject(
            \Magento\GiftCard\Helper\Catalog\Product\Configuration::class,
            [
                'context' => $context,
                'ctlgProdConfigur' => $this->ctlgProdConfigur,
                'escaper' => $this->escaper
            ]
        );
    }

    public function testGetGiftcardOptions()
    {
        $expected = [
            [
                'label' => 'Gift Card Sender',
                'value' => 'sender_name &lt;sender@test.com&gt;',
            ],
            [
                'label' => 'Gift Card Recipient',
                'value' => 'recipient_name &lt;recipient@test.com&gt;'
            ],
            [
                'label' => 'Gift Card Message',
                'value' => 'some message'
            ],
        ];

        $itemMock = $this->getMockBuilder(\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->prepareCustomOption($itemMock, 'giftcard_sender_name', 'sender_name', 0, 'sender_name');
        $this->prepareCustomOption($itemMock, 'giftcard_sender_email', 'sender_email', 1, 'sender@test.com');
        $this->prepareCustomOption($itemMock, 'giftcard_recipient_name', 'recipient_name', 2, 'recipient_name');
        $this->prepareCustomOption($itemMock, 'giftcard_recipient_email', 'recipient_email', 3, 'recipient@test.com');
        $this->prepareCustomOption($itemMock, 'giftcard_message', 'giftcard_message', 4, 'some message');

        $this->assertEquals($expected, $this->helper->getGiftcardOptions($itemMock));
    }

    /**
     * @param $itemMock
     * @param $code
     * @param $value
     * @param $index
     * @param $result
     * @return mixed
     */
    private function prepareCustomOption($itemMock, $code, $value, $index, $result)
    {
        $optionMock = $this->getMockBuilder(
            \Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock->expects($this->at($index))
            ->method('getOptionByCode')
            ->with($code)
            ->will($this->returnValue($optionMock));

        $optionMock->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($value));

        $this->escaper->expects($this->at($index))
            ->method('escapeHtml')
            ->with($value)
            ->will($this->returnValue($result));

        return $result;
    }

    public function testPrepareCustomOptionWithoutValue()
    {
        $code = 'option_code';

        $itemMock = $this->getMockBuilder(\Magento\Catalog\Model\Product\Configuration\Item\ItemInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $optionMock = $this->getMockBuilder(
            \Magento\Catalog\Model\Product\Configuration\Item\Option\OptionInterface::class
        )
            ->disableOriginalConstructor()
            ->getMock();

        $itemMock->expects($this->once())
            ->method('getOptionByCode')
            ->with($code)
            ->will($this->returnValue($optionMock));
        $optionMock->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue(null));

        $this->assertFalse($this->helper->prepareCustomOption($itemMock, $code));
    }
}
