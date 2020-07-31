<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCatalog\Test\Unit\Model\Indexer\Table;

class StrategyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Strategy object
     *
     * @var \Magento\AdvancedCatalog\Model\Indexer\Table\Strategy
     */
    protected $_model;

    /**
     * Resource mock
     *
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_resourceMock;

    /**
     * Adapter mock
     *
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_adapterMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $strategyMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->_resourceMock = $this->createMock(\Magento\Framework\App\ResourceConnection::class);
        $this->_adapterMock = $this->createMock(\Magento\Framework\DB\Adapter\Pdo\Mysql::class);
        $this->_resourceMock->expects($this->any())->method('getConnection')->willReturn($this->_adapterMock);
        $this->strategyMock = $this->createMock(
            \Magento\Catalog\Model\ResourceModel\Product\Indexer\TemporaryTableStrategy::class
        );

        $this->_model = new \Magento\AdvancedCatalog\Model\Indexer\Table\Strategy(
            $this->_resourceMock,
            $this->strategyMock
        );
    }

    /**
     * Test use idx table switcher
     *
     * @return void
     */
    public function testUseIdxTable()
    {
        $this->assertEquals(false, $this->_model->getUseIdxTable());
        $this->_model->setUseIdxTable(false);
        $this->assertEquals(false, $this->_model->getUseIdxTable());
        $this->_model->setUseIdxTable(true);
        $this->assertEquals(true, $this->_model->getUseIdxTable());
        $this->_model->setUseIdxTable();
        $this->assertEquals(false, $this->_model->getUseIdxTable());
    }

    /**
     * Test prepare table name with using idx table
     *
     * @return void
     */
    public function testPrepareTableNameUseIdxTable()
    {
        $this->strategyMock->expects($this->once())->method('prepareTableName')->with('test')->willReturn('test_idx');
        $this->assertEquals('test_idx', $this->_model->prepareTableName('test'));
    }
}
