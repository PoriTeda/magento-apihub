<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VersionsCms\Test\Unit\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor;

use Magento\Framework\Api\Filter;
use Magento\Store\Ui\Component\Listing\Column\Store;
use Magento\VersionsCms\Model\Api\SearchCriteria\CollectionProcessor\FilterProcessor\HierarchyNodeStoreFilter;

class HierarchyNodeStoreFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var  HierarchyNodeStoreFilter */
    private $filter;

    public function setUp()
    {
        $this->filter = new HierarchyNodeStoreFilter();
    }

    public function testApplyStoreFilter()
    {
        $collectionMock =
            $this->getMockBuilder(\Magento\VersionsCms\Model\ResourceModel\Hierarchy\Node\Collection::class)
                ->disableOriginalConstructor()
                ->getMock();
        $filterMock = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $filterMock->expects($this->once())
            ->method('getValue')
            ->willReturn('1');
        $this->filter->apply($filterMock, $collectionMock);
    }
}
