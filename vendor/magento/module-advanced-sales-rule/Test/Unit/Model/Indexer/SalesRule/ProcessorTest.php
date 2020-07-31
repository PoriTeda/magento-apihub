<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedSalesRule\Test\Unit\Model\Indexer\SalesRule;

use Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor;

/**
 * Class ProcessorTest
 */
class ProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor
     */
    protected $model;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexerRegistry;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * Setup the test
     */
    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $className = \Magento\Framework\Indexer\IndexerRegistry::class;
        $this->indexerRegistry = $this->createMock($className);

        $this->model = $this->objectManager->getObject(
            \Magento\AdvancedSalesRule\Model\Indexer\SalesRule\Processor::class,
            [
                'indexerRegistry' => $this->indexerRegistry,
            ]
        );
    }

    /**
     * test GetIndexer
     */
    public function testGetIndexer()
    {
        $className = \Magento\Indexer\Model\Indexer::class;
        $indexer = $this->createMock($className);

        $this->indexerRegistry->expects($this->any())
            ->method('get')
            ->willReturn($indexer);
        $this->assertSame($indexer, $this->model->getIndexer());
    }
}
