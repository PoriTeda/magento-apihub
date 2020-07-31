<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Action;

class FullTest extends \PHPUnit\Framework\TestCase
{
    public function testFullReindex()
    {
        $ruleFactoryMock = $this->createPartialMock(
            \Magento\TargetRule\Model\RuleFactory::class,
            ['create']
        );

        $collectionFactoryMock = $this->createPartialMock(
            \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory::class,
            ['create']
        );

        $resourceMock = $this->createMock(\Magento\TargetRule\Model\ResourceModel\Index::class);

        $collectionFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnValue([1, 2]));

        $resourceMock->expects($this->at(2))
            ->method('saveProductIndex')
            ->will($this->returnValue(1));

        $storeManagerMock = $this->getMockForAbstractClass(\Magento\Store\Model\StoreManagerInterface::class);
        $timezoneMock = $this->getMockForAbstractClass(\Magento\Framework\Stdlib\DateTime\TimezoneInterface::class);

        $model = new \Magento\TargetRule\Model\Indexer\TargetRule\Action\Full(
            $ruleFactoryMock,
            $collectionFactoryMock,
            $resourceMock,
            $storeManagerMock,
            $timezoneMock
        );

        $model->execute();
    }
}
