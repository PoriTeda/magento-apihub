<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reminder\Test\Unit\Controller\Adminhtml\Reminder;

use Magento\Reminder\Test\Unit\Controller\Adminhtml\AbstractReminder;

class DeleteTest extends AbstractReminder
{
    /**
     * @var \Magento\Reminder\Controller\Adminhtml\Reminder\Delete
     */
    protected $deleteController;

    protected function setUp()
    {
        parent::setUp();

        $this->deleteController = new \Magento\Reminder\Controller\Adminhtml\Reminder\Delete(
            $this->context,
            $this->coreRegistry,
            $this->ruleFactory,
            $this->conditionFactory,
            $this->dataFilter,
            $this->timeZoneResolver
        );
    }

    public function testExecute()
    {
        $this->initRule();

        $this->rule->expects($this->at(1))->method('delete')->willReturn(true);
        $this->messageManager->expects($this->once())
            ->method('addSuccess')
            ->with(__('You deleted the reminder rule.'))
            ->willReturn(true);

        $this->redirect('adminhtml/*/', []);

        $this->deleteController->execute();
    }

    public function testExecuteWithException()
    {
        $this->initRule();
        $exception = new \Magento\Framework\Exception\LocalizedException(
            __('Please correct the reminder rule you requested.')
        );
        $this->rule->expects($this->once())->method('delete')->will($this->throwException($exception));
        $this->messageManager->expects($this->once())
            ->method('addError')->with(__('Please correct the reminder rule you requested.'));
        $this->redirect('adminhtml/*/edit', ['id' => 1]);

        $this->deleteController->execute();
    }

    public function testExecuteWithException2()
    {
        $this->initRuleWithException();
        $exception = new \Exception('Exception message');
        $this->ruleFactory->expects($this->once())
            ->method('create')->will($this->throwException($exception));
        $this->messageManager->expects($this->once())
            ->method('addError')->with(__('We can\'t delete the reminder rule right now.'));
        $this->objectManagerMock->expects($this->once())
            ->method('get')->with(\Psr\Log\LoggerInterface::class)->willReturn($this->logger);
        $this->logger->expects($this->once())->method('critical')->with($exception)->willReturn(0);
        $this->redirect('adminhtml/*/', []);

        $this->deleteController->execute();
    }
}
