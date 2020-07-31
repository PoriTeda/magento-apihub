<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog\Event;

use Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event\Index;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;

class IndexTest extends \Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog\AbstractEventTest
{
    /**
     * @var \Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event\Index
     */
    protected $index;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->index = new Index(
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
        $titleMock = $this->getMockBuilder(\Magento\Framework\View\Page\Title::class)
            ->disableOriginalConstructor()
            ->getMock();
        $titleMock
            ->expects($this->once())
            ->method('prepend')
            ->with(new Phrase('Events'));

        $this->viewMock
            ->expects($this->any())
            ->method('getPage')
            ->willReturn(
                new DataObject(
                    ['config' => new DataObject(
                        ['title' => $titleMock]
                    )]
                )
            );

        $this->index->execute();
    }
}
