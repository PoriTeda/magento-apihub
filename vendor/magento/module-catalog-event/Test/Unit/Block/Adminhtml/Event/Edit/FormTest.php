<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Test\Unit\Block\Adminhtml\Event\Edit;

use Magento\CatalogEvent\Block\Adminhtml\Event\Edit\Form;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Unit test for Magento\CatalogEvent\Block\Adminhtml\Event\Edit\Form
 */
class FormTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogEvent\Block\Adminhtml\Event\Edit\Form
     */
    protected $form;

    /**
     * @var \Magento\Backend\Block\Template\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registryMock;

    /**
     * @var \Magento\Framework\Data\FormFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $formFactoryMock;

    /**
     * @var \Magento\Backend\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backendHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryFactoryMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->contextMock = (new ObjectManager($this))->getObject(\Magento\Backend\Block\Widget\Context::class);
        $this->registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->formFactoryMock = $this->getMockBuilder(\Magento\Framework\Data\FormFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->backendHelperMock = $this->getMockBuilder(\Magento\Backend\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->categoryFactoryMock = $this->getMockBuilder(\Magento\Catalog\Model\CategoryFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->form = new Form(
            $this->contextMock,
            $this->registryMock,
            $this->formFactoryMock,
            $this->backendHelperMock,
            $this->categoryFactoryMock
        );
    }

    /**
     * @return void
     */
    public function testGetActionUrl()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject $urlBuilderMock */
        $urlBuilderMock = $this->contextMock->getUrlBuilder();
        $urlBuilderMock
            ->expects($this->once())
            ->method('getUrl')
            ->with('adminhtml/*/save', ['_current' => true])
            ->willReturn('Result');

        $this->assertEquals('Result', $this->form->getActionUrl());
    }

    /**
     * @return void
     */
    public function testGetEvent()
    {
        $this->registryMock
            ->expects($this->once())
            ->method('registry')
            ->with('magento_catalogevent_event')
            ->willReturn('Result');

        $this->assertEquals('Result', $this->form->getEvent());
    }
}
