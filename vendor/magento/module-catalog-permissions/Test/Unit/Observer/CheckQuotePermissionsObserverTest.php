<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Test\Unit\Observer;

use Magento\CatalogPermissions\Observer\CheckQuotePermissionsObserver;
use Magento\Framework\DataObject;

/**
 * Test for \Magento\CatalogPermissions\Observer\CheckQuotePermissionsObserver
 */
class CheckQuotePermissionsObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogPermissions\Observer\CheckQuotePermissionsObserver
     */
    protected $observer;

    /**
     * @var \Magento\CatalogPermissions\App\ConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $permissionsConfig;

    /**
     * @var \Magento\CatalogPermissions\Model\Permission\Index|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $permissionIndex;

    /**
     * @var \Magento\Framework\Event\Observer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventObserverMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->permissionsConfig = $this->createMock(\Magento\CatalogPermissions\App\ConfigInterface::class);
        $this->permissionIndex = $this->createMock(\Magento\CatalogPermissions\Model\Permission\Index::class);

        $this->eventObserverMock = $this->getMockBuilder(\Magento\Framework\Event\Observer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->observer = new CheckQuotePermissionsObserver(
            $this->permissionsConfig,
            $this->createMock(\Magento\Customer\Model\Session::class),
            $this->permissionIndex,
            $this->createMock(\Magento\CatalogPermissions\Helper\Data::class)
        );
    }

    /**
     * @param int $step
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function preparationData($step = 0)
    {
        $quoteMock = $this->createMock(\Magento\Quote\Model\Quote::class);

        if ($step == 0) {
            $quoteMock->expects($this->exactly(3))
                ->method('getAllItems')
                ->will($this->returnValue([]));
        } else {
            $quoteItems = $this->createPartialMock(
                \Magento\Eav\Model\Entity\Collection\AbstractCollection::class,
                ['getProductId', 'setDisableAddToCart', 'getParentItem', 'getDisableAddToCart']
            );

            $quoteItems->expects($this->exactly(5))
                ->method('getProductId')
                ->will($this->returnValue(1));

            $quoteItems->expects($this->once())
                ->method('getParentItem')
                ->will($this->returnValue(0));

            $quoteItems->expects($this->once())
                ->method('getDisableAddToCart')
                ->will($this->returnValue(0));

            $quoteMock->expects($this->exactly(3))
                ->method('getAllItems')
                ->will($this->returnValue([$quoteItems]));
        }

        if ($step == 1) {
            $this->permissionIndex->expects($this->exactly(1))
                ->method('getIndexForProduct')
                ->will($this->returnValue([]));
        } elseif ($step == 2) {
            $this->permissionIndex->expects($this->exactly(1))
                ->method('getIndexForProduct')
                ->will($this->returnValue([1 => true]));
        }

        $cartMock = $this->createMock(\Magento\AdvancedCheckout\Model\Cart::class);
        $cartMock->expects($this->once())
            ->method('getQuote')
            ->will($this->returnValue($quoteMock));

        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getCart']);
        $eventMock->expects($this->once())
            ->method('getCart')
            ->will($this->returnValue($cartMock));

        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent']);
        $observerMock->expects($this->once())
            ->method('getEvent')
            ->will($this->returnValue($eventMock));

        return $observerMock;
    }

    /**
     * @return void
     */
    public function testCheckQuotePermissionsPermissionsConfigDisabled()
    {
        $this->permissionsConfig->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(false));

        $observerMock = $this->createMock(\Magento\Framework\Event\Observer::class);
        $this->assertEquals($this->observer, $this->observer->execute($observerMock));
    }

    /**
     * @param int $step
     * @dataProvider dataSteps
     * @return void
     */
    public function testCheckQuotePermissionsPermissionsConfigEnabled($step)
    {
        $this->permissionsConfig->expects($this->once())
            ->method('isEnabled')
            ->will($this->returnValue(true));

        $observer = $this->preparationData($step);
        $this->assertEquals($this->observer, $this->observer->execute($observer));
    }

    /**
     * @return array
     */
    public function dataSteps()
    {
        return [[0], [1], [2]];
    }
}
