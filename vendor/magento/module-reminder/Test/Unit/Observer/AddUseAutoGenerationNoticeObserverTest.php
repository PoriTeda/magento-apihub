<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Test\Unit\Observer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class AddUseAutoGenerationNoticeObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reminder\Observer\AddUseAutoGenerationNoticeObserver
     */
    private $model;

    /**
     * @var \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eventObserver;

    /**
     * @return void
     */
    protected function setUp()
    {
        $helper = new ObjectManager($this);

        $this->eventObserver = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->setMethods(['getCollection', 'getRule', 'getForm', 'getEvent'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = $helper->getObject(
            \Magento\Reminder\Observer\AddUseAutoGenerationNoticeObserver::class
        );
    }

    /**
     * @return void
     */
    public function testAddUseAutoGenerationNotice()
    {
        $formMock = $this->getMockBuilder(\Magento\Framework\Data\Form::class)
            ->setMethods(['getElement', 'setNote', 'getNote'])
            ->disableOriginalConstructor()
            ->getMock();
        $formMock->expects($this->once())->method('getElement')->with('use_auto_generation')->willReturnSelf();
        $formMock->expects($this->once())->method('setNote');
        $formMock->expects($this->once())->method('getNote');

        $this->eventObserver->expects($this->once())->method('getForm')->willReturn($formMock);
        $this->model->execute($this->eventObserver);
    }
}
