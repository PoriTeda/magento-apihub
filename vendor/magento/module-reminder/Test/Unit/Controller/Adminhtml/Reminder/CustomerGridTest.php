<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reminder\Test\Unit\Controller\Adminhtml\Reminder;

use Magento\Reminder\Test\Unit\Controller\Adminhtml\AbstractReminder;

class CustomerGridTest extends AbstractReminder
{
    public function testExecute()
    {
        $this->initRule();
        $this->view->expects($this->any())->method('getLayout')->willReturn($this->layout);
        $this->layout->expects($this->any())->method('createBlock')
            ->with(\Magento\Reminder\Block\Adminhtml\Reminder\Edit\Tab\Customers::class)->willReturn($this->block);
        $this->response->expects($this->once())->method('setBody')->willReturn(true);
        $this->block->expects($this->once())->method('toHtml')->willReturn(true);

        $customerGridController = new \Magento\Reminder\Controller\Adminhtml\Reminder\CustomerGrid(
            $this->context,
            $this->coreRegistry,
            $this->ruleFactory,
            $this->conditionFactory,
            $this->dataFilter,
            $this->timeZoneResolver
        );
        $customerGridController->execute();
    }
}
