<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reminder\Test\Unit\Observer;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class AddSalesRuleFilterObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reminder\Observer\AddSalesRuleFilterObserver
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
            \Magento\Reminder\Observer\AddSalesRuleFilterObserver::class
        );
    }

    /**
     * @return void
     */
    public function testAddSalesRuleFilter()
    {
        $collection = $this->getMockBuilder(\Magento\SalesRule\Model\ResourceModel\Rule\Collection::class)
            ->setMethods(['addAllowedSalesRulesFilter'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventObserver->expects($this->once())->method('getCollection')->will($this->returnValue($collection));
        $collection->expects($this->once())->method('addAllowedSalesRulesFilter');

        $this->model->execute($this->eventObserver);
    }
}
