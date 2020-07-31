<?php
/**
 * @category    Magento
 * @package     Magento_TargetRule
 * @subpackage  unit_tests
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\TargetRule\Test\Unit\Model\Indexer\TargetRule;

class AbstractProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AbstractProcessor
     */
    protected $_processor;

    /**
     * @var \Magento\TargetRule\Model\Indexer\TargetRule\Status\Container|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_statusContainer;

    /**
     * @var \Magento\Indexer\Model\Indexer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_indexer;

    /**
     * @var \Magento\Framework\Indexer\IndexerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $indexerRegistryMock;

    /**
     * @var \Magento\Indexer\Model\Indexer\State|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_state;

    protected function setUp()
    {
        $this->_indexer = $this->createPartialMock(\Magento\Indexer\Model\Indexer::class, ['getState', 'load']);

        $this->_statusContainer = $this->createPartialMock(
            \Magento\TargetRule\Model\Indexer\TargetRule\Status\Container::class,
            ['setFullReindexPassed', 'isFullReindexPassed']
        );

        $this->indexerRegistryMock = $this->createPartialMock(
            \Magento\Framework\Indexer\IndexerRegistry::class,
            ['get']
        );

        $this->_processor = $this->getMockForAbstractClass(
            \Magento\TargetRule\Model\Indexer\TargetRule\AbstractProcessor::class,
            [$this->indexerRegistryMock, $this->_statusContainer]
        );
    }

    public function testIsFullReindexPassed()
    {
        $this->_statusContainer->expects($this->once())
            ->method('isFullReindexPassed')
            ->with($this->_processor->getIndexerId());
        $this->_processor->isFullReindexPassed();
    }

    public function testSetFullReindexPassed()
    {
        $this->_state = $this->createPartialMock(
            \Magento\Indexer\Model\Indexer\State::class,
            ['setStatus', 'save', '__sleep', '__wakeup']
        );

        $this->_state->expects($this->once())
            ->method('setStatus')
            ->with(\Magento\Framework\Indexer\StateInterface::STATUS_VALID)
            ->will($this->returnSelf());

        $this->_state->expects($this->once())
            ->method('save');

        $this->_statusContainer->expects($this->once())
            ->method('setFullReindexPassed')
            ->with($this->_processor->getIndexerId());

        $this->_indexer->expects($this->once())
            ->method('getState')
            ->will($this->returnValue($this->_state));
        $this->indexerRegistryMock->expects($this->once())
            ->method('get')
            ->with('')
            ->will($this->returnValue($this->_indexer));

        $this->_processor->setFullReindexPassed();
    }
}
