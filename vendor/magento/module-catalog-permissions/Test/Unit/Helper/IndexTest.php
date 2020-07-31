<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogPermissions\Test\Unit\Helper;

use Magento\CatalogPermissions\Helper\Index;

/**
 * Unit-test for \Magento\CatalogPermissions\Helper\Index
 */
class IndexTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogPermissions\Helper\Index
     */
    protected $helper;

    /**
     * @var \Magento\Framework\App\ResourceConnection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resourceMock;

    /**
     * @var \Magento\Framework\DB\Adapter\Pdo\Mysql|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $connectionMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->connectionMock = $this->getMockBuilder(\Magento\Framework\DB\Adapter\Pdo\Mysql::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceMock = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceMock
            ->expects($this->any())
            ->method('getTableName')
            ->will($this->returnArgument(0));
        $this->resourceMock
            ->expects($this->any())
            ->method('getConnection')
            ->willReturn(
                $this->connectionMock
            );

        $this->helper = new Index(
            $this->resourceMock
        );
    }

    /**
     * @return void
     */
    public function testGetChildCategories()
    {
        $selectPathMock = $this->getMockForSelectPath();

        $selectCategoryMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectCategoryMock
            ->expects($this->once())
            ->method('from')
            ->with('catalog_category_entity', ['entity_id'])
            ->will($this->returnSelf());
        $selectCategoryMock
            ->expects($this->once())
            ->method('order')
            ->with('level ASC')
            ->will($this->returnSelf());
        $selectCategoryMock
            ->expects($this->once())
            ->method('orWhere')
            ->with('path LIKE ?', '1/2/%')
            ->will($this->returnSelf());

        $this->connectionMock
            ->expects($this->any())
            ->method('fetchCol')
            ->will(
                $this->returnValueMap(
                    [
                        [$selectPathMock, [], ['1/2']],
                        [$selectCategoryMock, [], [3, 4]]
                    ]
                )
            );

        $this->connectionMock
            ->expects($this->atLeastOnce())
            ->method('select')
            ->will($this->onConsecutiveCalls(
                $selectPathMock,
                $selectCategoryMock
            ));

        $this->assertEquals([3, 4], $this->helper->getChildCategories([987]));
    }

    /**
     * @return void
     */
    public function testGetCategoryList()
    {
        $selectPathMock = $this->getMockForSelectPath();

        $selectCategoryMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectCategoryMock
            ->expects($this->once())
            ->method('from')
            ->with('catalog_category_entity', ['entity_id', 'path'])
            ->will($this->returnSelf());
        $selectCategoryMock
            ->expects($this->once())
            ->method('order')
            ->with('level ASC')
            ->will($this->returnSelf());
        $selectCategoryMock
            ->expects($this->once())
            ->method('where')
            ->with('path LIKE ?', '1/2/%')
            ->will($this->returnSelf());
        $selectCategoryMock
            ->expects($this->once())
            ->method('orWhere')
            ->with('entity_id IN (?)', [1, 2])
            ->will($this->returnSelf());

        $this->connectionMock
            ->expects($this->once())
            ->method('fetchCol')
            ->with($selectPathMock)
            ->willReturn(['1/2']);
        $this->connectionMock
            ->expects($this->once())
            ->method('fetchPairs')
            ->with($selectCategoryMock)
            ->willReturn([123123]);

        $this->connectionMock
            ->expects($this->atLeastOnce())
            ->method('select')
            ->will($this->onConsecutiveCalls(
                $selectPathMock,
                $selectCategoryMock
            ));

        $this->assertEquals([123123], $this->helper->getCategoryList([987]));
    }

    /**
     * @return void
     */
    public function testGetProductList()
    {
        $selectMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectMock
            ->expects($this->once())
            ->method('from')
            ->with('catalog_category_product', 'product_id')
            ->will($this->returnSelf());
        $selectMock
            ->expects($this->once())
            ->method('distinct')
            ->with(true)
            ->will($this->returnSelf());
        $selectMock
            ->expects($this->once())
            ->method('where')
            ->with('category_id IN (?)', [1, 2, 3])
            ->will($this->returnSelf());

        $this->connectionMock
            ->expects($this->any())
            ->method('getTransactionLevel')
            ->willReturn(1);
        $this->connectionMock
            ->expects($this->once())
            ->method('select')
            ->willReturn($selectMock);
        $this->connectionMock
            ->expects($this->once())
            ->method('fetchCol')
            ->with($selectMock)
            ->willReturn('some result');

        $this->assertEquals('some result', $this->helper->getProductList([1, 2, 3]));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getMockForSelectPath()
    {
        $selectPathMock = $this->getMockBuilder(\Magento\Framework\DB\Select::class)
            ->disableOriginalConstructor()
            ->getMock();
        $selectPathMock
            ->expects($this->once())
            ->method('from')
            ->with('catalog_category_entity', ['path'])
            ->will($this->returnSelf());
        $selectPathMock
            ->expects($this->once())
            ->method('where')
            ->with('entity_id IN (?)', [987])
            ->will($this->returnSelf());

        return $selectPathMock;
    }
}
