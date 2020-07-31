<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Product;

class RuleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule
     */
    protected $_ruleIndexer;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_ruleProductProcessor;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_productRuleProcessor;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Action\Full|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_actionFull;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Action\Clean|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_actionClean;

    /**
     * @var Rule\Action\CleanDeleteProduct|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_actionCleanDeleteProduct;

    protected function setUp()
    {
        $this->_ruleProductProcessor = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor::class
        );
        $this->_productRuleProcessor = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor::class
        );
        $this->_actionFull = $this->createMock(\Magento\TargetRule\Model\Indexer\TargetRule\Action\Full::class);
        $actionRow = $this->createMock(\Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Row::class);
        $actionRows = $this->createMock(\Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\Rows::class);
        $this->_actionClean = $this->createMock(\Magento\TargetRule\Model\Indexer\TargetRule\Action\Clean::class);
        $this->_actionCleanDeleteProduct = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Action\CleanDeleteProduct::class
        );
        $this->_ruleIndexer = new \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule(
            $actionRow,
            $actionRows,
            $this->_actionFull,
            $this->_ruleProductProcessor,
            $this->_productRuleProcessor,
            $this->_actionClean,
            $this->_actionCleanDeleteProduct
        );
    }

    public function testFullReindexIfNotExecutedRelatedIndexer()
    {
        $this->_actionFull->expects($this->once())
            ->method('execute');
        $this->_ruleProductProcessor->expects($this->once())
            ->method('isFullReindexPassed')
            ->will($this->returnValue(false));
        $this->_ruleProductProcessor->expects($this->once())
            ->method('setFullReindexPassed');
        $this->_ruleIndexer->executeFull();
    }

    public function testFullReindexIfRelatedIndexerPassed()
    {
        $this->_actionFull->expects($this->never())
            ->method('execute');
        $this->_ruleProductProcessor->expects($this->once())
            ->method('isFullReindexPassed')
            ->will($this->returnValue(true));
        $this->_ruleProductProcessor->expects($this->never())
            ->method('setFullReindexPassed');
        $this->_ruleIndexer->executeFull();
    }
}
