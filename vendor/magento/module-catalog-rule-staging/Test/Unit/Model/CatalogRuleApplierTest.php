<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Test\Unit\Model;

use Magento\CatalogRuleStaging\Model\CatalogRuleApplier;
use Magento\CatalogRule\Model\Indexer\Rule\RuleProductProcessor;
use Magento\Framework\Indexer\IndexerRegistry;

class CatalogRuleApplierTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IndexerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $indexerRegistryMock;

    /**
     * @var RuleProductProcessor|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ruleProductProcessorMock;

    /**
     * @var CatalogRuleApplier
     */
    private $model;

    protected function setUp()
    {
        $this->ruleProductProcessorMock = $this->getMockBuilder(RuleProductProcessor::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->indexerRegistryMock = $this->getMockBuilder(IndexerRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new CatalogRuleApplier(
            $this->ruleProductProcessorMock,
            $this->indexerRegistryMock
        );
    }

    public function testExecute()
    {
        $entityIds = [1];
        $indexerMock = $this->getMockBuilder(\Magento\Framework\Indexer\IndexerInterface::class)
            ->getMockForAbstractClass();

        $this->ruleProductProcessorMock->expects($this->atLeastOnce())
            ->method('markIndexerAsInvalid')
            ->willReturnSelf();
        $this->indexerRegistryMock->expects($this->at(0))
            ->method('get')
            ->with(\Magento\CatalogRule\Model\Indexer\Product\ProductRuleProcessor::INDEXER_ID)
            ->willReturn($indexerMock);
        $this->indexerRegistryMock->expects($this->at(1))
            ->method('get')
            ->with(\Magento\Catalog\Model\Indexer\Product\Price\Processor::INDEXER_ID)
            ->willReturn($indexerMock);
        $indexerMock->expects($this->any())->method('invalidate')->willReturnSelf();

        $this->model->execute($entityIds);
    }

    public function testExecuteWithNoEntities()
    {
        $result = $this->model->execute([]);
        $this->assertNull($result);
    }
}
