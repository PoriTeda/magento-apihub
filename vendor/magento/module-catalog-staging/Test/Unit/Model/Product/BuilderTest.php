<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Model\Product;

use Magento\CatalogStaging\Model\Product\Builder as BuilderModel;

class BuilderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $resolverMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $linkConverterMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $linkRepositoryMock;

    /**
     * @var BuilderModel
     */
    protected $builder;

    protected function setUp()
    {
        $this->resolverMock = $this->getMockBuilder(\Magento\Catalog\Model\Product\Link\Resolver::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->linkConverterMock = $this->getMockBuilder(\Magento\Catalog\Model\Product\Link\Converter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->linkRepositoryMock = $this->getMockBuilder(\Magento\Catalog\Model\ProductLink\Repository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new BuilderModel(
            $this->resolverMock,
            $this->linkConverterMock,
            $this->linkRepositoryMock
        );
    }

    public function testBuild()
    {
        $groupedLinkData = [30, 50, 70];
        $prototypeMock = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->linkConverterMock->expects($this->once())
            ->method('convertLinksToGroupedArray')
            ->with($prototypeMock)
            ->willReturn($groupedLinkData);
        $this->linkRepositoryMock->expects($this->once())
            ->method('getList')
            ->with($prototypeMock);
        $this->resolverMock->expects($this->once())
            ->method('override')
            ->with($groupedLinkData);
        $result = $this->builder->build($prototypeMock);
        $this->assertEquals(get_class($prototypeMock), get_class($result));
    }
}
