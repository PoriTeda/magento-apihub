<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Test\Unit\Observer;

class UpdateOptionCustomerSegmentationObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $valueFactoryMock;

    /**
     * @var \Magento\PersistentHistory\Observer\UpdateOptionCustomerSegmentationObserver
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->valueFactoryMock = $this->createPartialMock(
            \Magento\Framework\App\Config\ValueFactory::class,
            ['create']
        );
        $this->subject = $objectManager->getObject(
            \Magento\PersistentHistory\Observer\UpdateOptionCustomerSegmentationObserver::class,
            ['valueFactory' => $this->valueFactoryMock]
        );
    }

    public function testUpdateOptionIfEventValueIsNull()
    {
        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent']);
        $eventDataObjectMock = $this->createPartialMock(
            \Magento\PersistentHistory\Model\Adminhtml\System\Config\Cart::class,
            ['getValue', '__wakeup']
        );

        $eventDataObjectMock->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue(null));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getDataObject']);
        $eventMock->expects($this->once())->method('getDataObject')->will($this->returnValue($eventDataObjectMock));

        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));
        $this->subject->execute($observerMock);
    }

    public function testUpdateOptionSuccess()
    {
        $scopeId = 1;
        $scope = ['scope' => 'scope_value'];

        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent']);
        $eventDataObjectMock = $this->createPartialMock(
            \Magento\PersistentHistory\Model\Adminhtml\System\Config\Cart::class,
            ['getValue', '__wakeup', 'getScope', 'getScopeId']
        );

        $eventDataObjectMock->expects($this->once())
            ->method('getValue')
            ->will($this->returnValue('value'));
        $eventDataObjectMock->expects($this->once())
            ->method('getScope')
            ->will($this->returnValue($scope));
        $eventDataObjectMock->expects($this->once())
            ->method('getScopeId')
            ->will($this->returnValue($scopeId));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getDataObject']);
        $eventMock->expects($this->once())->method('getDataObject')->will($this->returnValue($eventDataObjectMock));

        $observerMock->expects($this->once())->method('getEvent')->will($this->returnValue($eventMock));

        $valueMock = $this->createPartialMock(
            \Magento\Framework\App\Config\Value::class,
            ['setScope', 'setScopeId', 'setValue', 'save', 'setPath', '__wakeup']
        );
        $this->valueFactoryMock->expects($this->once())->method('create')->will($this->returnValue($valueMock));

        $valueMock->expects($this->once())->method('setScope')->with($scope)->will($this->returnSelf());
        $valueMock->expects($this->once())->method('setScopeId')->with($scopeId)->will($this->returnSelf());
        $valueMock->expects($this->once())->method('setValue')->with(true)->will($this->returnSelf());
        $valueMock->expects($this->once())->method('save')->will($this->returnSelf());
        $valueMock->expects($this->once())->method('setPath')
            ->with(\Magento\PersistentHistory\Helper\Data::XML_PATH_PERSIST_CUSTOMER_AND_SEGM)
            ->will($this->returnSelf());

        $this->subject->execute($observerMock);
    }
}
