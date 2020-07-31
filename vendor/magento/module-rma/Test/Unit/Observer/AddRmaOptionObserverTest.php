<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Test\Unit\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event;
use Magento\Sales\Block\Adminhtml\Reorder\Renderer\Action as Renderer;
use Magento\Framework\View\Element\UiComponent\DataProvider\Document as DocumentDataProvider;

class AddRmaOptionObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Rma\Observer\AddRmaOptionObserver
     */
    private $model;

    /**
     * @var \Magento\Rma\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $rmaData;

    /**
     * @var EventObserver|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventObserver;

    /**
     * @var Event|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    /**
     * @var Renderer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $renderer;

    /**
     * @var DocumentDataProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $row;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->rmaData = $this->getMockBuilder(\Magento\Rma\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventObserver = $this->getMockBuilder(EventObserver::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEvent'])
            ->getMock();
        $this->event = $this->getMockBuilder(Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRenderer', 'getRow'])
            ->getMock();
        $this->renderer = $this->getMockBuilder(Renderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->row = $this->getMockBuilder(DocumentDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $objectManagerHelper->getObject(
            \Magento\Rma\Observer\AddRmaOptionObserver::class,
            [
                'rmaData' => $this->rmaData
            ]
        );
    }

    public function testExecute()
    {
        $id = "1";
        $url = "http://magento.dev/admin/admin/rma/new/order_id/1/";
        $reorderAction = [
            '@' => ['href' => $url],
            '#' => new \Magento\Framework\Phrase('Return'),
        ];

        $this->eventObserver->expects($this->atLeastOnce())
            ->method('getEvent')
            ->willReturn($this->event);
        $this->event->expects($this->once())
            ->method('getRenderer')
            ->willReturn($this->renderer);
        $this->event->expects($this->once())
            ->method('getRow')
            ->willReturn($this->row);
        $this->rmaData->expects($this->once())
            ->method('canCreateRma')
            ->willReturn(true);
        $this->row->expects($this->once())
            ->method('getId')
            ->willReturn($id);

        $this->renderer->expects($this->once())
            ->method('getUrl')
            ->with('adminhtml/rma/new', ['order_id' => $id])
            ->willReturn($url);

        $this->renderer->expects($this->once())
            ->method('addToActions')
            ->with($reorderAction)
            ->willReturnSelf();

        $this->model->execute($this->eventObserver);
    }
}
