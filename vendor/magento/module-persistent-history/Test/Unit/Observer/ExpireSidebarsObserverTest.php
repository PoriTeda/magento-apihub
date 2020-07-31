<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PersistentHistory\Test\Unit\Observer;

class ExpireSidebarsObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $historyHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $compareItemMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $comparedFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $viewedFactoryMock;

    /**
     * @var \Magento\PersistentHistory\Observer\ExpireSidebarsObserver
     */
    protected $subject;

    protected function setUp()
    {
        /** @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager  */
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $this->historyHelperMock = $this->createPartialMock(
            \Magento\PersistentHistory\Helper\Data::class,
            ['isCompareProductsPersist', 'isComparedProductsPersist']
        );
        $this->compareItemMock = $this->createMock(\Magento\Catalog\Model\Product\Compare\Item::class);
        $this->comparedFactoryMock = $this->createPartialMock(
            \Magento\Reports\Model\Product\Index\ComparedFactory::class,
            ['create']
        );
        $this->viewedFactoryMock = $this->createPartialMock(
            \Magento\Reports\Model\Product\Index\ViewedFactory::class,
            ['create']
        );

        $this->subject = $objectManager->getObject(
            \Magento\PersistentHistory\Observer\ExpireSidebarsObserver::class,
            [
                'ePersistentData' => $this->historyHelperMock,
                'compareItem' => $this->compareItemMock,
                'comparedFactory' => $this->comparedFactoryMock,
                'viewedFactory' => $this->viewedFactoryMock
            ]
        );
    }

    public function testSidebarExpireDataIfCompareProductsNotPersistAndComparedProductsNotPersist()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->historyHelperMock->expects($this->once())
            ->method('isCompareProductsPersist')
            ->will($this->returnValue(false));
        $this->historyHelperMock->expects($this->exactly(2))
            ->method('isComparedProductsPersist')
            ->will($this->returnValue(false));
        $this->subject->execute($observerMock);
    }

    public function testSidebarExpireDataIfComparedProductsNotPersist()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->historyHelperMock->expects($this->once())
            ->method('isCompareProductsPersist')
            ->will($this->returnValue(true));

        $this->compareItemMock->expects($this->once())->method('bindCustomerLogout')->will($this->returnSelf());

        $this->historyHelperMock->expects($this->exactly(2))
            ->method('isComparedProductsPersist')
            ->will($this->returnValue(false));
        $this->subject->execute($observerMock);
    }

    public function testSidebarExpireDataIfCompareProductsNotPersist()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->historyHelperMock->expects($this->once())
            ->method('isCompareProductsPersist')
            ->will($this->returnValue(false));

        $this->historyHelperMock->expects($this->exactly(2))
            ->method('isComparedProductsPersist')
            ->will($this->returnValue(true));

        $comparedMock = $this->createMock(\Magento\Reports\Model\Product\Index\Compared::class);
        $comparedMock->expects($this->once())->method('purgeVisitorByCustomer')->will($this->returnSelf());
        $comparedMock->expects($this->once())->method('calculate')->will($this->returnSelf());
        $this->comparedFactoryMock->expects($this->once())->method('create')->will($this->returnValue($comparedMock));

        $viewedMock = $this->createMock(\Magento\Reports\Model\Product\Index\Viewed::class);
        $viewedMock->expects($this->once())->method('purgeVisitorByCustomer')->will($this->returnSelf());
        $viewedMock->expects($this->once())->method('calculate')->will($this->returnSelf());
        $this->viewedFactoryMock->expects($this->once())->method('create')->will($this->returnValue($viewedMock));

        $this->subject->execute($observerMock);
    }

    public function testSidebarExpireDataSuccess()
    {
        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->historyHelperMock->expects($this->once())
            ->method('isCompareProductsPersist')
            ->will($this->returnValue(true));

        $this->compareItemMock->expects($this->once())->method('bindCustomerLogout')->will($this->returnSelf());

        $this->historyHelperMock->expects($this->exactly(2))
            ->method('isComparedProductsPersist')
            ->will($this->returnValue(true));

        $comparedMock = $this->createMock(\Magento\Reports\Model\Product\Index\Compared::class);
        $comparedMock->expects($this->once())->method('purgeVisitorByCustomer')->will($this->returnSelf());
        $comparedMock->expects($this->once())->method('calculate')->will($this->returnSelf());
        $this->comparedFactoryMock->expects($this->once())->method('create')->will($this->returnValue($comparedMock));

        $viewedMock = $this->createMock(\Magento\Reports\Model\Product\Index\Viewed::class);
        $viewedMock->expects($this->once())->method('purgeVisitorByCustomer')->will($this->returnSelf());
        $viewedMock->expects($this->once())->method('calculate')->will($this->returnSelf());
        $this->viewedFactoryMock->expects($this->once())->method('create')->will($this->returnValue($viewedMock));

        $this->subject->execute($observerMock);
    }
}
