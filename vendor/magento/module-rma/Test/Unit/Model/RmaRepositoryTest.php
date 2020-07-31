<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Test\Unit\Model;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Model\ResourceModel\Rma\CollectionFactory;
use Magento\Rma\Model\RmaFactory;
use Magento\Rma\Model\RmaRepository;

class RmaRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * rmaFactory
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $rmaFactoryMock;

    /**
     * Collection Factory
     *
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $rmaCollectionFactorMock;

    /** @var  \PHPUnit_Framework_MockObject_MockObject */
    private $collectionProcessor;

    /** @var  RmaRepository */
    private $repository;

    protected function setUp()
    {
        $this->rmaFactoryMock = $this->getMockBuilder(RmaFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->rmaCollectionFactorMock = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->collectionProcessor = $this->createMock(CollectionProcessorInterface::class);

        $this->repository = new RmaRepository(
            $this->rmaFactoryMock,
            $this->rmaCollectionFactorMock,
            $this->collectionProcessor
        );
    }

    public function testFind()
    {
        $rma1 = $this->getMockBuilder(\Magento\Rma\Model\Rma::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rma2 = $this->getMockBuilder(\Magento\Rma\Model\Rma::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rma1->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(1);
        $rma1->expects($this->once())
            ->method('load')
            ->with(1);
        $rma2->expects($this->once())
            ->method('load')
            ->with(2);
        $rma2->expects($this->atLeastOnce())
            ->method('getId')
            ->willReturn(2);
        $items = new \ArrayObject([$rma1, $rma2]);
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rmaCollection = $this->getMockBuilder(\Magento\Rma\Model\ResourceModel\Rma\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $rmaCollection->expects($this->atLeastOnce())
            ->method('getIterator')
            ->willReturn($items);
        $this->rmaCollectionFactorMock->expects($this->once())
            ->method('create')
            ->willReturn($rmaCollection);
        $this->collectionProcessor->expects($this->once())
            ->method('process')
            ->with($searchCriteria, $rmaCollection);
        $rmaCollection->expects($this->once())
            ->method('getAllIds')
            ->willReturn([1, 2]);
        $this->repository->find($searchCriteria);
    }
}
