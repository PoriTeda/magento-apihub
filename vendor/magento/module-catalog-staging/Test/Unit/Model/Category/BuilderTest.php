<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Model\Category;

use Magento\CatalogStaging\Model\Category\Builder;

class BuilderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $defaultBuilder;

    /** @var Builder */
    protected $builder;

    public function setUp()
    {
        $this->defaultBuilder = $this->getMockBuilder(\Magento\Staging\Model\Entity\Builder\DefaultBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new Builder(
            $this->defaultBuilder
        );
    }

    public function testBuild()
    {
        $prototype = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['isObjectNew', 'setRowId'])
            ->getMock();

        $this->defaultBuilder->expects($this->once())
            ->method('build')
            ->with($prototype)
            ->willReturn($prototype);
        $prototype->expects($this->once())
            ->method('isObjectNew')
            ->with(true);
        $prototype->expects($this->once())
            ->method('setRowId')
            ->with(null);
        $this->assertEquals($prototype, $this->builder->build($prototype));
    }
}
