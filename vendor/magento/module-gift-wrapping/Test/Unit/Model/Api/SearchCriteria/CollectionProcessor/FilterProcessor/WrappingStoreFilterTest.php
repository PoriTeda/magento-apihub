<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Test\Unit\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor;

use Magento\GiftWrapping\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor\WrappingStoreFilter;

/**
 * Class StatusFilterTest
 * @package Magento\GiftWrapping\Test\Unit\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor
 */
class WrappingStoreFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var  WrappingStoreFilter */
    private $model;

    public function setUp()
    {
        $this->model = new WrappingStoreFilter();
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
        $collectionMock->expects($this->once())
            ->method('addStoreAttributesToResult')
            ->with(1);
        $this->assertTrue($this->model->apply($filterMock, $collectionMock));
    }
}
