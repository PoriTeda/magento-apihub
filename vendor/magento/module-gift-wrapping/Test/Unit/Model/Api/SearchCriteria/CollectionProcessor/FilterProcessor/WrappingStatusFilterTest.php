<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Test\Unit\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor;

use Magento\GiftWrapping\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor\WrappingStatusFilter;

/**
 * Class StatusFilterTest
 * @package Magento\GiftWrapping\Test\Unit\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor
 */
class WrappingStatusFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var  WrappingStatusFilter */
    private $model;

    public function setUp()
    {
        $this->model = new WrappingStatusFilter();
    }

    public function testApply()
    {
        $collectionMock = $this->getMockBuilder(\Magento\GiftWrapping\Model\ResourceModel\Wrapping\Collection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $filterMock = $this->getMockBuilder(\Magento\Framework\Api\Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filterMock->expects($this->once())
            ->method('getValue')
            ->willReturn('1');
        $selectMock = $this->getMockBuilder(\Magento\Framework\Db\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionMock->expects($this->once())
            ->method('getSelect')
            ->willReturn($selectMock);
        $selectMock->expects($this->once())
            ->method('where')
            ->with('main_table.status = ?', 1);
        $this->assertTrue($this->model->apply($filterMock, $collectionMock));
    }
}
