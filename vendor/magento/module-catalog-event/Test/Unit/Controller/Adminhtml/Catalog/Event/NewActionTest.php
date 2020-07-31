<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog\Event;

use Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event\NewAction;
use Magento\Framework\DataObject;
use Magento\Framework\Phrase;

class NewActionTest extends \Magento\CatalogEvent\Test\Unit\Controller\Adminhtml\Catalog\AbstractEventTest
{
    /**
     * @var \Magento\CatalogEvent\Controller\Adminhtml\Catalog\Event\NewAction
     */
    protected $newAction;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->newAction = new NewAction(
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
        $this->requestMock
            ->expects($this->once())
            ->method('setActionName')
            ->with('edit');
        $this->requestMock
            ->expects($this->once())
            ->method('setDispatched')
            ->with(false);

        $this->newAction->execute();
    }
}
