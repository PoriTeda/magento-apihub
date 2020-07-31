<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Test\Unit\Block\Adminhtml\Renderer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class AmountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftCard\Block\Adminhtml\Renderer\Amount
     */
    protected $block;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);
        $this->block = $objectManager->getObject(\Magento\GiftCard\Block\Adminhtml\Renderer\Amount::class);
    }

    public function testGetValues()
    {
        $data = [
            [
                'website_id' => '1',
                'value' => '15.000',
            ],
            [
                'website_id' => '2',
                'value' => '0.500',
            ],
            [
                'website_id' => '0',
                'value' => '3.000',
            ],
            [
                'website_id' => '1',
                'value' => '6.000',
            ],
            [
                'website_id' => '2',
                'value' => '0.900',
            ],
        ];

        $expected = [
            [
                'website_id' => '0',
                'value' => '3.000',
            ],
            [
                'website_id' => '1',
                'value' => '6.000',
            ],
            [
                'website_id' => '1',
                'value' => '15.000',
            ],
            [
                'website_id' => '2',
                'value' => '0.500',
            ],
            [
                'website_id' => '2',
                'value' => '0.900',
            ],
        ];

        $element = $this->getMockBuilder(\Magento\Framework\Data\Form\Element\AbstractElement::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->block->setElement($element);

        $element->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue($data));

        $this->assertEquals($expected, $this->block->getValues());
    }
}
