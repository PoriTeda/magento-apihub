<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class CatalogProductDeleteCommitAfterObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tested observer
     *
     * @var \Magento\TargetRule\Observer\CatalogProductDeleteCommitAfterObserver
     */
    protected $_observer;

    /**
     * Product-Rule indexer mock
     *
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_productRuleIndexer;

    protected function setUp()
    {
        $this->_productRuleIndexer = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule::class
        );

        $this->_observer = (new ObjectManager($this))->getObject(
            \Magento\TargetRule\Observer\CatalogProductDeleteCommitAfterObserver::class,
            [
                'productRuleIndexer' => $this->_productRuleIndexer,
            ]
        );
    }

    public function testCatalogProductDeleteCommitAfter()
    {
        $productMock = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            ['getId', '__sleep', '__wakeup']
        );
        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getEvent']);
        $eventMock = $this->createPartialMock(\Magento\Framework\Event::class, ['getProduct']);

        $eventMock->expects($this->once())
            ->method('getProduct')
            ->will($this->returnValue($productMock));

        $observerMock->expects($this->once())
            ->method('getEvent')
            ->will($this->returnValue($eventMock));

        $productMock->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $this->_productRuleIndexer->expects($this->once())
            ->method('cleanAfterProductDelete')
            ->with(1);

        $this->_observer->execute($observerMock);
    }
}
