<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog\Event;

use Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event\CategoriesJson;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;

class CategoriesJsonTest extends \Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog\AbstractEventTest
{
    /**
     * @var \Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event\CategoriesJson
     */
    protected $categoriesJson;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->categoriesJson = new CategoriesJson(
            $this->contextMock,
            $this->registryMock,
            $this->eventFactoryMock,
            $this->dateTimeMock,
            $this->storeManagerMock
        );
    }

    /**
     * @return void
     */
    public function testExecute()
    {
        $categoryBlockMock = $this->getMockBuilder(\Magento\CatalogEvent\Block\Adminhtml\Event\Edit\Category::class)
            ->disableOriginalConstructor()
            ->getMock();
        $categoryBlockMock
            ->expects($this->once())
            ->method('getTreeArray')
            ->with(null, true, 1)
            ->willReturn('some result');

        $this->responseMock
            ->expects($this->once())
            ->method('representJson')
            ->with('some result');

        $this->layoutMock
            ->expects($this->once())
            ->method('createBlock')
            ->with(\Magento\CatalogEvent\Block\Adminhtml\Event\Edit\Category::class)
            ->willReturn($categoryBlockMock);

        $this->categoriesJson->execute();
    }
}
