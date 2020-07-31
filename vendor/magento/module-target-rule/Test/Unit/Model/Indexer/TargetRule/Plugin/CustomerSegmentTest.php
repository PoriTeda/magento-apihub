<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule\Plugin;

class CustomerSegmentTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Plugin\CustomerSegment
     */
    protected $_model;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_ruleProductMock;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_productRuleMock;

    protected function setUp()
    {
        $this->_ruleProductMock = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Rule\Product\Processor::class
        );
        $this->_productRuleMock = $this->createMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Product\Rule\Processor::class
        );
        $this->_model = new \Magento\TargetRule\Model\Indexer\TargetRule\Plugin\CustomerSegment(
            $this->_productRuleMock,
            $this->_ruleProductMock
        );
    }

    public function testCustomerSegmentChanges()
    {
        $subjectMock = $this->createMock(\Magento\CustomerSegment\Model\Segment::class);
        $this->_productRuleMock->expects($this->exactly(2))
            ->method('markIndexerAsInvalid');

        $this->_ruleProductMock->expects($this->exactly(2))
            ->method('markIndexerAsInvalid');

        $this->assertEquals(
            $subjectMock,
            $this->_model->afterDelete($subjectMock)
        );

        $this->assertEquals(
            $subjectMock,
            $this->_model->afterSave($subjectMock)
        );
    }
}
