<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

class LoggingTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GiftRegistry\Model\Logging */
    protected $logging;

    /** @var ObjectManagerHelper */
    protected $objectManagerHelper;

    /** @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestInterface;

    /**
     * @var \Magento\Logging\Model\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventModel;

    /**
     * @var \Magento\Logging\Model\Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $processor;

    protected function setUp()
    {
        $this->requestInterface = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->eventModel = $this->getMockBuilder(\Magento\Logging\Model\Event::class)
            ->setMethods(['setInfo', '__wakeup', '__sleep'])
            ->disableOriginalConstructor()->getMock();
        $this->processor = $this->getMockBuilder(\Magento\Logging\Model\Processor::class)
            ->disableOriginalConstructor()->getMock();

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->logging = $this->objectManagerHelper->getObject(
            \Magento\GiftRegistry\Model\Logging::class,
            [
                'request' => $this->requestInterface
            ]
        );
    }

    public function testPostDispatchTypeSave()
    {
        $this->requestInterface->expects($this->once())->method('getParam')
            ->with('type')
            ->will($this->returnValue(['type_id' => 'Some Type Id']));
        $this->eventModel->expects($this->once())->method('setInfo')->with('Some Type Id')->will($this->returnSelf());
        $this->logging->postDispatchTypeSave([], $this->eventModel, $this->processor);
    }

    public function testPostDispatchShare()
    {
        $this->requestInterface->expects($this->at(0))->method('getParam')
            ->with('emails')
            ->will($this->returnValue(['some@example.com']));
        $this->requestInterface->expects($this->at(1))->method('getParam')
            ->with('message')
            ->will($this->returnValue('Gift Registry Message'));

        $changes = $this->getMockBuilder(\Magento\Logging\Model\Event\Changes::class)
            ->disableOriginalConstructor()->getMock();

        $this->processor->expects($this->any())->method('createChanges')->will($this->returnValue($changes));
        $this->processor->expects($this->any())->method('addEventChanges')->with($changes)->will($this->returnSelf());

        $event = $this->logging->postDispatchShare([], $this->eventModel, $this->processor);
        $this->assertSame($this->eventModel, $event);
    }
}
