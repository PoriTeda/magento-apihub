<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Controller\Guest;

class AddCommentTest extends \Magento\Rma\Test\Unit\Controller\GuestTest
{
    /**
     * @var string
     */
    protected $name = 'AddComment';

    public function testAddCommentAction()
    {
        $entityId = 7;
        $orderId = 5;
        $comment = 'comment';

        $this->request->expects($this->any())
            ->method('getParam')
            ->with('entity_id')
            ->will($this->returnValue($entityId));
        $this->request->expects($this->any())
            ->method('getPost')
            ->with('comment')
            ->will($this->returnValue($comment));

        $this->rmaHelper->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));

        $this->salesGuestHelper->expects($this->once())
            ->method('loadValidOrder')
            ->with($this->request)
            ->will($this->returnValue(true));

        $rma = $this->createPartialMock(
            \Magento\Rma\Model\Rma::class,
            ['__wakeup', 'load', 'getCustomerId', 'getId', 'getOrderId']
        );
        $rma->expects($this->once())
            ->method('load')
            ->with($entityId)
            ->will($this->returnSelf());
        $rma->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($entityId));
        $rma->expects($this->any())
            ->method('getOrderId')
            ->will($this->returnValue($orderId));

        $order = $this->createPartialMock(
            \Magento\Sales\Model\Order::class,
            ['__wakeup', 'getCustomerId', 'load', 'getId']
        );
        $order->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($orderId));

        $history = $this->createMock(\Magento\Rma\Model\Rma\Status\History::class);
        $history->expects($this->once())
            ->method('sendCustomerCommentEmail');
        $history->expects($this->once())
            ->method('saveComment')
            ->with($comment, true, false);

        $this->objectManager->expects($this->at(0))
            ->method('create')
            ->with(\Magento\Rma\Model\Rma::class)
            ->will($this->returnValue($rma));
        $this->objectManager->expects($this->at(1))
            ->method('create')
            ->with(\Magento\Rma\Model\Rma\Status\History::class)
            ->will($this->returnValue($history));

        $this->coreRegistry->expects($this->at(0))
            ->method('registry')
            ->with('current_order')
            ->will($this->returnValue($order));
        $this->coreRegistry->expects($this->at(1))
            ->method('registry')
            ->with('current_rma')
            ->will($this->returnValue($rma));

        $this->resultRedirect->expects($this->once())
            ->method('setPath')
            ->with('*/*/view', ['entity_id' => $entityId])
            ->willReturnSelf();

        $this->assertInstanceOf(\Magento\Framework\Controller\Result\Redirect::class, $this->controller->execute());
    }
}
