<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Test\Unit\Block\Event;

use Magento\CatalogEvent\Block\Event\Lister;

/**
 * Unit test for Magento\CatalogEvent\Block\Event\Lister
 */
class ListerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogEvent\Block\Event\Lister
     */
    protected $lister;

    /**
     * @var \Magento\Framework\View\Element\Template\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $resolverMock;

    /**
     * @var \Magento\CatalogEvent\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $catalogEventHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var \Magento\Catalog\Helper\Category|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $catalogCategoryHelperMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->contextMock = $this->getMockBuilder(\Magento\Framework\View\Element\Template\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->resolverMock = $this->getMockBuilder(\Magento\CatalogEvent\Model\DateResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogEventHelperMock = $this->getMockBuilder(\Magento\CatalogEvent\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder(
            \Magento\CatalogEvent\Model\ResourceModel\Event\CollectionFactory::class
        )
            ->disableOriginalConstructor()
            ->getMock();
        $this->catalogCategoryHelperMock = $this->getMockBuilder(\Magento\Catalog\Helper\Category::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->lister = new Lister(
            $this->contextMock,
            $this->resolverMock,
            $this->catalogEventHelperMock,
            $this->collectionFactoryMock,
            $this->catalogCategoryHelperMock
        );
    }

    /**
     * @return void
     */
    public function testGetCategoryUrl()
    {
        $parameterMock = $this->createMock(\Magento\Catalog\Model\Category::class);

        $this->catalogCategoryHelperMock
            ->expects($this->once())
            ->method('getCategoryUrl')
            ->with($parameterMock)
            ->willReturn('Result');

        $this->assertEquals('Result', $this->lister->getCategoryUrl($parameterMock));
    }

    /**
     * @return void
     */
    public function testGetEventImageUrl()
    {
        $eventMock = $this->createMock(\Magento\CatalogEvent\Model\Event::class);
        $this->catalogEventHelperMock
            ->expects($this->once())
            ->method('getEventImageUrl')
            ->with($eventMock)
            ->willReturn('Result');

        $this->assertEquals('Result', $this->lister->getEventImageUrl($eventMock));
    }
}
