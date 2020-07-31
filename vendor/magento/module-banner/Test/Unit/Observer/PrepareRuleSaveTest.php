<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Banner\Test\Unit\Observer;

class PrepareRuleSaveTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Banner\Observer\PrepareRuleSave
     */
    protected $prepareRuleSaveObserver;

    /**
     * @var \Magento\Framework\Event\Observer
     */
    protected $eventObserver;

    /**
     * @var \Magento\Backend\Helper\Js|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $adminhtmlJs;

    /**
     * @var \Magento\Framework\Event|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $event;

    /**
     * @var \Magento\Framework\App\Request\Http|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $http;

    protected function setUp()
    {
        $this->adminhtmlJs = $this->getMockBuilder(\Magento\Backend\Helper\Js::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->prepareRuleSaveObserver = new \Magento\Banner\Observer\PrepareRuleSave(
            $this->adminhtmlJs
        );
    }

    public function testPrepareRuleSave()
    {
        $this->http = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->http->expects($this->any())->method('getPost')->with('related_banners')->will(
            $this->returnValue('test')
        );
        $this->adminhtmlJs->expects($this->once())->method('decodeGridSerializedInput')->with('test')->will(
            $this->returnValue('test')
        );
        $this->http->expects($this->any())->method('setPost')->with('related_banners', 'test');
        $this->event = $this->getMockBuilder(\Magento\Framework\Event::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRequest', 'setPost', 'getPost'])
            ->getMock();
        $this->event->expects($this->any())->method('getRequest')->will($this->returnValue($this->http));
        $this->eventObserver = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventObserver->expects($this->any())->method('getEvent')->will($this->returnValue($this->event));
        $this->assertInstanceOf(
            \Magento\Banner\Observer\PrepareRuleSave::class,
            $this->prepareRuleSaveObserver->execute($this->eventObserver)
        );
    }
}
