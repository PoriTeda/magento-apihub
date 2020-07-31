<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Controller\Adminhtml\Rma;

class AddCommentTest extends \Magento\Rma\Test\Unit\Controller\Adminhtml\RmaTest
{
    protected $name = 'AddComment';

    public function testAddCommentsAction()
    {
        $commentText = 'some comment';
        $visibleOnFront = true;
        $blockContents = [
            $commentText,
        ];
        $layoutMock = $this->createMock(\Magento\Framework\View\LayoutInterface::class);
        $blockMock = $this->createMock(\Magento\Framework\View\Element\BlockInterface::class);
        $jsonHelperMock = $this->createMock(\Magento\Framework\Json\Helper\Data::class);

        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->will(
                $this->returnValue(
                    [
                        'comment' => $commentText,
                        'is_visible_on_front' => $visibleOnFront,
                        'is_customer_notified' => true,
                    ]
                )
            );
        $this->coreRegistryMock->expects($this->once())
            ->method('registry')
            ->with('current_rma')
            ->will($this->returnValue($this->rmaModelMock));
        $this->rmaModelMock->expects($this->once())
            ->method('getId')
            ->willReturn(10);
        $this->statusHistoryMock->expects($this->once())
            ->method('setRmaEntityId')
            ->with(10)
            ->willReturnSelf();
        $this->statusHistoryMock->expects($this->once())
            ->method('setComment')
            ->with($commentText);
        $this->viewMock->expects($this->once())
            ->method('getLayout')
            ->will($this->returnValue($layoutMock));
        $layoutMock->expects($this->once())
            ->method('getBlock')
            ->with('comments_history')
            ->will($this->returnValue($blockMock));
        $blockMock->expects($this->once())
            ->method('toHtml')
            ->will($this->returnValue($blockContents));
        $this->objectManagerMock->expects($this->once())
            ->method('get')
            ->with(\Magento\Framework\Json\Helper\Data::class)
            ->will($this->returnValue($jsonHelperMock));
        $jsonHelperMock->expects($this->once())
            ->method('jsonEncode')
            ->will($this->returnValue($commentText));

        $this->responseMock->expects($this->once())
            ->method('representJson')
            ->with($commentText);

        $this->assertNull($this->action->execute());
    }
}
