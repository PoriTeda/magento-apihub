<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCard\Test\Unit\Block\Adminhtml\Renderer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class OpenAmountTest
 */
class OpenAmountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftCard\Block\Adminhtml\Renderer\OpenAmount
     */
    protected $block;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $factory;

    /**
     * @var \Magento\Framework\Data\Form\Element\Checkbox
     */
    protected $element;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder(\Magento\Framework\Data\Form\Element\Factory::class)
            ->disableOriginalConstructor()->setMethods(['create'])->getMock();
        $objectManager = new ObjectManager($this);
        $this->element = $objectManager->getObject(\Magento\Framework\Data\Form\Element\Checkbox::class);
        $form = $this->getMockBuilder(\Magento\Framework\Data\Form::class)->disableOriginalConstructor()
            ->setMethods(['getHtmlIdPrefix', 'getHtmlIdSuffix'])
            ->getMock();
        $form->expects($this->any())->method('getHtmlIdPrefix')->willReturn('');
        $form->expects($this->any())->method('getHtmlIdSuffix')->willReturn('');

        $this->factory->expects($this->once())->method('create')->willReturn($this->element);
        $this->block = $objectManager->getObject(
            \Magento\GiftCard\Block\Adminhtml\Renderer\OpenAmount::class,
            [
                'factoryElement' => $this->factory
            ]
        );
        $this->block->setForm($form);
    }

    public function testGetElementHtml()
    {
        $this->block->setReadonlyDisabled(true);
        $this->assertContains('disabled', $this->block->getElementHtml());
    }
}
