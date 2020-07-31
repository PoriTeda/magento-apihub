<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\TargetRule\Test\Unit\Controller\Adminhtml\Targetrule;

use Magento\Framework\DataObject;
use Magento\Framework\Phrase;
use Magento\TargetRule\Controller\Adminhtml\Targetrule\Index;

class IndexTest extends AbstractTest
{
    /**
     * @var Index
     */
    protected $controller;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->controller = new Index(
            $this->contextMock,
            $this->registryMock,
            $this->dateMock
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $menuModelMock = $this->getMockBuilder(\Magento\Backend\Model\Menu::class)
            ->disableOriginalConstructor()
            ->getMock();
        $menuModelMock
            ->expects($this->any())
            ->method('getParentItems')
            ->willReturn([]);

        $this->menuBlockMock
            ->expects($this->any())
            ->method('getMenuModel')
            ->willReturn($menuModelMock);

        $titleMock = $this->getMockBuilder(\Magento\Framework\View\Page\Title::class)
            ->disableOriginalConstructor()
            ->getMock();
        $titleMock
            ->expects($this->exactly(1))
            ->method('prepend')
            ->withConsecutive(
                [new Phrase('Related Products Rules')]
            );
        $this->viewMock
            ->expects($this->any())
            ->method('getPage')
            ->willReturn(new DataObject(['config' => new DataObject(['title' => $titleMock])]));

        $this->controller->execute();
    }
}
