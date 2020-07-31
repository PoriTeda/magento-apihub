<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Rule\Product\Action;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class RowTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Tested model
     *
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Row
     */
    protected $model;

    /**
     * Product factory mock
     *
     * @var \Magento\Catalog\Model\ProductFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productFactoryMock;

    /**
     * Rule Factory mock
     *
     * @var \Magento\TargetRule\Model\RuleFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $ruleFactoryMock;

    protected function setUp()
    {
        $objectManager = new ObjectManager($this);

        $this->ruleFactoryMock = $this->createPartialMock(\Magento\TargetRule\Model\RuleFactory::class, ['create']);
        $this->model = $objectManager->getObject(
            \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Row::class,
            [
                'productFactory' => $this->createPartialMock(\Magento\Catalog\Model\ProductFactory::class, ['create']),
                'ruleFactory' => $this->ruleFactoryMock,
                'ruleCollectionFactory' => $this->createPartialMock(
                    \Magento\TargetRule\Model\ResourceModel\Rule\CollectionFactory::class,
                    ['create']
                ),
                'resource' => $this->createMock(\Magento\TargetRule\Model\ResourceModel\Index::class),
                'storeManager' => $this->getMockForAbstractClass(
                    \Magento\Store\Model\StoreManagerInterface::class,
                    [],
                    '',
                    false
                ),
                'localeDate' => $this->getMockForAbstractClass(
                    \Magento\Framework\Stdlib\DateTime\TimezoneInterface::class,
                    [],
                    '',
                    false
                ),
            ]
        );
    }

    /**
     * Test for exec with empty IDs
     *
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage We can't rebuild the index for an undefined product.
     */
    public function testEmptyId()
    {
        $this->model->execute(null);
    }

    public function testCleanProductPagesCache()
    {
        $ruleId = 1;
        $oldProductIds = [1, 2];
        $newProductIds = [2, 3];
        $productsToClean = array_unique(array_merge($oldProductIds, $newProductIds));
        $rule = $this->createPartialMock(
            \Magento\TargetRule\Model\Rule::class,
            ['load', 'getResource', 'getMatchingProductIds', 'getId', '__sleep', '__wakeup']
        );
        $rule->expects($this->once())->method('load')->with($ruleId);
        $ruleResource = $this->createPartialMock(\Magento\TargetRule\Model\ResourceModel\Rule::class, [
                '__sleep',
                '__wakeup',
                'getAssociatedEntityIds',
                'unbindRuleFromEntity',
                'bindRuleToEntity',
                'cleanCachedDataByProductIds'
            ]);
        $ruleResource->expects($this->once())
            ->method('getAssociatedEntityIds')
            ->with($ruleId, 'product')
            ->will($this->returnValue($oldProductIds));

        $ruleResource->expects($this->once())
            ->method('unbindRuleFromEntity')
            ->with($ruleId, [], 'product');

        $ruleResource->expects($this->once())
            ->method('bindRuleToEntity')
            ->with($ruleId, $newProductIds, 'product');

        $ruleResource->expects($this->once())
            ->method('cleanCachedDataByProductIds')
            ->with($productsToClean);

        $rule->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(1));

        $rule->expects($this->once())
            ->method('getMatchingProductIds')
            ->will($this->returnValue($newProductIds));

        $rule->expects($this->once())
            ->method('getResource')
            ->will($this->returnValue($ruleResource));

        $this->ruleFactoryMock->expects($this->once())
            ->method('create')
            ->will($this->returnValue($rule));

        $this->model->execute($ruleId);
    }
}
