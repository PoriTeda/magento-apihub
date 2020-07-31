<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Test\Unit\Model\Status;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Model\ResourceModel\Rma\CollectionFactory;
use Magento\Rma\Model\Rma\Status\HistoryFactory;
use Magento\Rma\Model\Rma\Status\HistoryRepository;
use Magento\Rma\Model\RmaFactory;
use Magento\Rma\Model\RmaRepository;

class HistoryRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * rmaFactory
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $historyFactoryMock;

    /**
     * Collection Factory
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $historyCollectionFactorMock;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    private $collectionProcessor;

    /** @var  HistoryRepository */
    private $repository;

    protected function setUp()
    {
        $this->historyFactoryMock = $this->getMockBuilder(HistoryFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->historyCollectionFactorMock =
            $this->getMockBuilder(\Magento\Rma\Model\ResourceModel\Rma\Status\History\CollectionFactory::class)
                ->setMethods(['create'])
                ->disableOriginalConstructor()
                ->getMock();

        $this->collectionProcessor = $this->createMock(CollectionProcessorInterface::class);

        $this->repository = new HistoryRepository(
            $this->historyFactoryMock,
            $this->historyCollectionFactorMock,
            $this->collectionProcessor
        );
    }

    public function testFind()
    {
        $history1 = $this->getMockBuilder(\Magento\Rma\Model\Rma\Status\History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $history2 = $this->getMockBuilder(\Magento\Rma\Model\Rma\Status\History::class)
            ->disableOriginalConstructor()
            ->getMock();
        $history1->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);
        $history1->expects($this->once())
            ->method('load')
            ->with(1);
        $history2->expects($this->once())
            ->method('load')
            ->with(2);
        $history2->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(2);
        $items = new \ArrayObject([$history1, $history2]);
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $historyCollection = $this->getMockBuilder(\Magento\Rma\Model\ResourceModel\Rma\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $historyCollection->expects($this->atLeastOnce())
            ->method('getIterator')
            ->willReturn($items);
        $this->historyCollectionFactorMock->expects($this->once())
            ->method('create')
            ->willReturn($historyCollection);
        $this->collectionProcessor->expects($this->once())
            ->method('process')
            ->with($searchCriteria, $historyCollection);
        $historyCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn([1, 2]);
        $this->repository->find($searchCriteria);
    }
}
