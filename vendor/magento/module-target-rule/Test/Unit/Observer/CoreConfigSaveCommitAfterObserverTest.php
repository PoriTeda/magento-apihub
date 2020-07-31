<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Observer;

use Magento\Catalog\Model\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class CoreConfigSaveCommitAfterObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tested observer
     *
     * @var \Magento\TargetRule\Observer\CoreConfigSaveCommitAfterObserver
     */
    protected $_observer;

    /**
     * Product-Rule processor mock
     *
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_productRuleProcessorMock;

    protected function setUp()
    {
        $this->_productRuleProcessorMock = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor::class
        );

        $this->_observer = (new ObjectManager($this))->getObject(
            \Magento\TargetRule\Observer\CoreConfigSaveCommitAfterObserver::class,
            [
                'productRuleIndexerProcessor' => $this->_productRuleProcessorMock,
            ]
        );
    }

    public function testCoreConfigSaveCommitAfter()
    {
        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getDataObject']);
        $dataObject = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getPath', 'isValueChanged']);

        $dataObject->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('customer/magento_customersegment/is_enabled'));

        $dataObject->expects($this->once())
            ->method('isValueChanged')
            ->will($this->returnValue(true));

        $observerMock->expects($this->exactly(2))
            ->method('getDataObject')
            ->will($this->returnValue($dataObject));

        $this->_productRuleProcessorMock->expects($this->once())
            ->method('markIndexerAsInvalid');

        $this->_observer->execute($observerMock);
    }

    public function testCoreConfigSaveCommitAfterNoChanges()
    {
        $observerMock = $this->createPartialMock(\Magento\Framework\Event\Observer::class, ['getDataObject']);
        $dataObject = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getPath', 'isValueChanged']);
        $dataObject->expects($this->once())
            ->method('getPath')
            ->will($this->returnValue('customer/magento_customersegment/is_enabled'));

        $dataObject->expects($this->once())
            ->method('isValueChanged')
            ->will($this->returnValue(false));

        $observerMock->expects($this->exactly(2))
            ->method('getDataObject')
            ->will($this->returnValue($dataObject));

        $this->_productRuleProcessorMock->expects($this->never())
            ->method('markIndexerAsInvalid');

        $this->_observer->execute($observerMock);
    }
}
