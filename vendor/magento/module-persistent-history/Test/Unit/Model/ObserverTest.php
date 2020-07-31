<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Test\Unit\Model;

class ObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $persistentHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sessionHelperMock;

    /**
     * @var \Magento\PersistentHistory\Model\Observer
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->persistentHelperMock = $this->createPartialMock(
            \Magento\PersistentHistory\Helper\Data::class,
            [
                'isOrderedItemsPersist',
                'isViewedProductsPersist',
                'isComparedProductsPersist',
                'isCompareProductsPersist',
            ]
        );
        $this->sessionHelperMock = $this->createPartialMock(\Magento\Persistent\Helper\Session::class, ['getSession']);
        $this->subject = $objectManager->getObject(
            \Magento\PersistentHistory\Model\Observer::class,
            ['ePersistentData' => $this->persistentHelperMock, 'persistentSession' => $this->sessionHelperMock]
        );
    }

    public function testInitReorderSidebarIfOrderItemsNotPersist()
    {
        $blockMock = $this->createMock(\Magento\Sales\Block\Reorder\Sidebar::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('isOrderedItemsPersist')
            ->will($this->returnValue(false));
        $this->subject->initReorderSidebar($blockMock);
    }

    public function testInitReorderSidebarSuccess()
    {
        $customerId = 100;
        $this->sessionHelperMock->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($this->getSessionMock()));

        $blockMock = $this->createPartialMock(
            \Magento\Sales\Block\Reorder\Sidebar::class,
            ['setCustomerId', '__wakeup', 'initOrders']
        );
        $this->persistentHelperMock->expects($this->once())
            ->method('isOrderedItemsPersist')
            ->will($this->returnValue(true));

        $blockMock->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->will($this->returnSelf());
        $blockMock->expects($this->never())->method('initOrders')->will($this->returnSelf());
        $this->subject->initReorderSidebar($blockMock);
    }

    public function testEmulateViewedProductsIfProductsNotPersist()
    {
        $blockMock = $this->createMock(\Magento\Reports\Block\Product\Viewed::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('isViewedProductsPersist')
            ->will($this->returnValue(false));
        $this->subject->emulateViewedProductsBlock($blockMock);
    }

    public function testEmulateViewedProductsSuccess()
    {
        $customerId = 100;
        $this->sessionHelperMock->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($this->getSessionMock()));

        $blockMock = $this->createPartialMock(
            \Magento\Reports\Block\Product\Viewed::class,
            ['getModel', 'setCustomerId', '__wakeup']
        );
        $this->persistentHelperMock->expects($this->once())
            ->method('isViewedProductsPersist')
            ->will($this->returnValue(true));

        $modelMock = $this->createPartialMock(
            \Magento\Reports\Model\Product\Index\AbstractIndex::class,
            ['setCustomerId', 'calculate', '__wakeup']
        );
        $modelMock->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->will($this->returnSelf());
        $modelMock->expects($this->once())->method('calculate')->will($this->returnSelf());

        $blockMock->expects($this->once())
            ->method('getModel')
            ->will($this->returnValue($modelMock));
        $blockMock->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->will($this->returnSelf());

        $this->subject->emulateViewedProductsBlock($blockMock);
    }

    public function testEmulateComparedProductsIfProductsNotPersist()
    {
        $blockMock = $this->createMock(\Magento\Reports\Block\Product\Compared::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('isComparedProductsPersist')
            ->will($this->returnValue(false));
        $this->subject->emulateComparedProductsBlock($blockMock);
    }

    public function testEmulateComparedProductsSuccess()
    {
        $customerId = 100;
        $this->sessionHelperMock->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($this->getSessionMock()));

        $blockMock = $this->createPartialMock(
            \Magento\Reports\Block\Product\Compared::class,
            ['getModel', 'setCustomerId', '__wakeup']
        );
        $this->persistentHelperMock->expects($this->once())
            ->method('isComparedProductsPersist')
            ->will($this->returnValue(true));

        $modelMock = $this->createPartialMock(
            \Magento\Reports\Model\Product\Index\AbstractIndex::class,
            ['setCustomerId', 'calculate', '__wakeup']
        );
        $modelMock->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->will($this->returnSelf());
        $modelMock->expects($this->once())->method('calculate')->will($this->returnSelf());

        $blockMock->expects($this->once())
            ->method('getModel')
            ->will($this->returnValue($modelMock));
        $blockMock->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->will($this->returnSelf());

        $this->subject->emulateComparedProductsBlock($blockMock);
    }

    public function testEmulateCompareProductListIfProductsNotPersistent()
    {
        $blockMock = $this->createMock(\Magento\Catalog\Block\Product\Compare\ListCompare::class);
        $this->persistentHelperMock->expects($this->once())
            ->method('isCompareProductsPersist')
            ->will($this->returnValue(false));
        $this->subject->emulateCompareProductsListBlock($blockMock);
    }

    public function testEmulateCompareProductListSuccess()
    {
        $customerId = 100;
        $this->sessionHelperMock->expects($this->once())
            ->method('getSession')
            ->will($this->returnValue($this->getSessionMock()));

        $blockMock = $this->createPartialMock(
            \Magento\Catalog\Block\Product\Compare\ListCompare::class,
            ['setCustomerId']
        );
        $this->persistentHelperMock->expects($this->once())
            ->method('isCompareProductsPersist')
            ->will($this->returnValue(true));
        $blockMock->expects($this->once())
            ->method('setCustomerId')
            ->with($customerId)
            ->will($this->returnSelf());
        $this->subject->emulateCompareProductsListBlock($blockMock);
    }

    protected function getSessionMock()
    {
        $customerId = 100;
        $sessionMock = $this->createPartialMock(
            \Magento\Persistent\Model\Session::class,
            ['getCustomerId', '__wakeup']
        );
        $sessionMock->expects($this->once())->method('getCustomerId')->will($this->returnValue($customerId));
        return $sessionMock;
    }
}
