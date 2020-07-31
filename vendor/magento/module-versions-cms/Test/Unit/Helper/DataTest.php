<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Helper;

class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldSet;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $elementInFieldSet;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $element;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $container;

    protected function setUp()
    {
        $this->fieldSet = $this->createMock(\Magento\Framework\Data\Form\Element\Fieldset::class);
        $this->elementInFieldSet = $this->createMock(\Magento\Framework\Data\Form\Element\AbstractElement::class);
        $this->element = $this->createMock(\Magento\Framework\Data\Form\Element\AbstractElement::class);
    }

    public function testAddAttributeToFormElements()
    {
        $attributeName = 'test-attribute';
        $attributeValue = 'test-value';

        $this->elementInFieldSet->expects($this->once())->method('setData')->with($attributeName, $attributeValue);

        $this->fieldSet->expects($this->once())->method('getType')->willReturn('fieldset');
        $this->fieldSet->expects($this->once())->method('getElements')->willReturn([$this->elementInFieldSet]);

        $this->element->expects($this->once())->method('setData')->with($attributeName, $attributeValue);

        $this->container = $this->createMock(\Magento\Framework\Data\Form\AbstractForm::class);
        $this->container->expects($this->once())->method('getElements')->willReturn([$this->fieldSet, $this->element]);

        /** @var \Magento\VersionsCms\Helper\Data $helper */
        $helper = (new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this))
            ->getObject(\Magento\VersionsCms\Helper\Data::class);
        $helper->addAttributeToFormElements($attributeName, $attributeValue, $this->container);
    }
}
