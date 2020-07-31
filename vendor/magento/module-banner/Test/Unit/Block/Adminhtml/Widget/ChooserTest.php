<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Test\Unit\Block\Adminhtml\Widget;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

class ChooserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObjectManagerHelper
     */
    protected $objectManagerHelper;

    /**
     * @var \Magento\Framework\Data\Form\Element\Factory|MockObject
     */
    protected $elementFactoryMock;

    /**
     * @var \Magento\Banner\Block\Adminhtml\Widget\Chooser|MockObject
     */
    protected $chooser;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->elementFactoryMock = $this->createMock(\Magento\Framework\Data\Form\Element\Factory::class);

        $contextMock = $this->createMock(\Magento\Backend\Block\Template\Context::class);
        $dataMock = $this->createMock(\Magento\Backend\Helper\Data::class);
        $bannerColFactory = $this->getMockBuilder(\Magento\Banner\Model\ResourceModel\Banner\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $bannerConfig = $this->createMock(\Magento\Banner\Model\Config::class);

        $this->chooser = $this->getMockBuilder(\Magento\Banner\Block\Adminhtml\Widget\Chooser::class)
            ->setMethods(['toHtml', '_construct'])
            ->setConstructorArgs(
                [
                    'context' => $contextMock,
                    'backendHelper' => $dataMock,
                    'bannerColFactory' => $bannerColFactory,
                    'bannerConfig' => $bannerConfig,
                    'elementFactory' => $this->elementFactoryMock
                ]
            )
            ->getMock();
    }

    /**
     * @return void
     */
    public function testPrepareElementHtml()
    {
        $elementId = 1;
        $elementData = 'Some data of element';
        $hiddenHtml = 'Some HTML';
        $toHtmlValue = 'to html';

        $this->chooser->expects($this->once())
            ->method('toHtml')
            ->willReturn($toHtmlValue);

        /** @var \Magento\Framework\Data\Form\AbstractForm|MockObject $formMock */
        $formMock = $this->getMockForAbstractClass(\Magento\Framework\Data\Form\AbstractForm::class, [], '', false);

        /** @var \Magento\Framework\Data\Form\Element\AbstractElement|MockObject $elementMock */
        $elementMock = $this->getMockBuilder(\Magento\Framework\Data\Form\Element\AbstractElement::class)
            ->setMethods(['getId', 'getValue', 'getData', 'getForm', 'setValue', 'setValueClass', 'setData'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $elementMock->expects($this->once())
            ->method('getId')
            ->willReturn($elementId);
        $elementMock->expects($this->once())
            ->method('getValue')
            ->willReturn('some value');
        $elementMock->expects($this->once())
            ->method('getData')
            ->willReturn($elementData);
        $elementMock->expects($this->once())
            ->method('getForm')
            ->willReturn($formMock);
        $elementMock->expects($this->once())
            ->method('setValue')
            ->with('')
            ->willReturnSelf();
        $elementMock->expects($this->once())
            ->method('setValueClass')
            ->with('value2')
            ->willReturnSelf();
        $elementMock->expects($this->any())
            ->method('setData')
            ->withConsecutive(
                ['css_class', 'grid-chooser'],
                ['after_element_html', $hiddenHtml . $toHtmlValue],
                ['no_wrap_as_addon', true]
            )
            ->willReturnSelf();

        /** @var \Magento\Framework\Data\Form\Element\Hidden|MockObject $hiddenMock */
        $hiddenMock = $this->createMock(\Magento\Framework\Data\Form\Element\Hidden::class);
        $hiddenMock->expects($this->once())
            ->method('setId')
            ->with($elementId)
            ->willReturnSelf();
        $hiddenMock->expects($this->once())
            ->method('setForm')
            ->with($formMock)
            ->willReturnSelf();
        $hiddenMock->expects($this->once())
            ->method('getElementHtml')
            ->willReturn($hiddenHtml);

        $this->elementFactoryMock->expects($this->once())
            ->method('create')
            ->with('hidden', ['data' => $elementData])
            ->willReturn($hiddenMock);

        $this->assertSame($elementMock, $this->chooser->prepareElementHtml($elementMock));
    }
}
