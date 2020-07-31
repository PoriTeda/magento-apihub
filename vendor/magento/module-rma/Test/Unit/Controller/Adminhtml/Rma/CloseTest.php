<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma;

class CloseTest extends \Magento\Rma\Test\Unit\Controller\Adminhtml\RmaTest
{
    protected $name = 'Close';

    public function testCloseAction()
    {
        $entityId = 1;
        $this->requestMock->expects($this->any())
            ->method('getParam')
            ->will(
                $this->returnValueMap(
                    [
                        ['entity_id', null, $entityId],
                    ]
                )
            );
        $this->rmaModelMock->expects($this->once())
            ->method('load')
            ->with($entityId)
            ->will($this->returnSelf());
        $this->rmaModelMock->expects($this->once())
            ->method('canClose')
            ->will($this->returnValue(true));
        $this->rmaModelMock->expects($this->once())
            ->method('close')
            ->will($this->returnSelf());
        $this->statusHistoryMock->expects($this->once())
            ->method('saveSystemComment');

        $this->assertNull($this->action->execute());
    }
}
