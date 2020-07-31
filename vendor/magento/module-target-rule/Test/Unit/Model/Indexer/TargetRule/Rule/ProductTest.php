<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Rule;

class ProductTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product
     */
    protected $_productIndexer;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_productRuleProcessor;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Action\Full|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_ruleProductIndexerFull;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_ruleProductProcessor;

    protected function setUp()
    {
        $this->_ruleProductProcessor = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor::class
        );
        $this->_productRuleProcessor = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor::class
        );
        $this->_ruleProductIndexerFull = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Action\Full::class
        );
        $ruleProductIndexerRows = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Rows::class
        );
        $ruleProductIndexerRow = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Action\Row::class
        );
        $this->_productIndexer = new \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product(
            $ruleProductIndexerRow,
            $ruleProductIndexerRows,
            $this->_ruleProductIndexerFull,
            $this->_productRuleProcessor,
            $this->_ruleProductProcessor
        );
    }

    public function testFullReindexIfNotExecutedRelatedIndexer()
    {
        $this->_ruleProductIndexerFull->expects($this->once())
            ->method('execute');
        $this->_productRuleProcessor->expects($this->once())
            ->method('isFullReindexPassed')
            ->will($this->returnValue(false));
        $this->_productRuleProcessor->expects($this->once())
            ->method('setFullReindexPassed');
        $this->_productIndexer->executeFull();
    }

    public function testFullReindexIfRelatedIndexerPassed()
    {
        $this->_ruleProductIndexerFull->expects($this->never())
            ->method('execute');
        $this->_productRuleProcessor->expects($this->once())
            ->method('isFullReindexPassed')
            ->will($this->returnValue(true));
        $this->_productRuleProcessor->expects($this->never())
            ->method('setFullReindexPassed');
        $this->_productIndexer->executeFull();
    }
}
