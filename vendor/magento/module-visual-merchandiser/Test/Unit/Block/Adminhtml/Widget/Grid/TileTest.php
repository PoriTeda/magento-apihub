<?php
/***
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Test\Unit\Block\Adminhtml\Widget\Grid;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class TileTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\VisualMerchandiser\Block\Adminhtml\Category\Merchandiser\Tile
     */
    protected $tileBlock;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $collection;

    /**
     * Set up instances and mock objects
     */
    protected function setUp()
    {
        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $request = $this->createMock(\Magento\Framework\App\Request\Http::class);
        $request->expects($this->atLeastOnce())->method('getParam')->will($this->returnValue(''));
        $request->expects($this->any())->method('has')->will($this->returnValue(false));

        $context = $this->createMock(\Magento\Backend\Block\Template\Context::class);
        $context->expects($this->any())->method('getRequest')->will($this->returnValue($request));

        $collection = $this->createMock(\Magento\Catalog\Model\ResourceModel\Product\Collection::class);
        $collection
            ->expects($this->any())
            ->method('getIterator')
            ->willReturn(new \ArrayIterator([]));
        $this->collection = $collection;

        $products = $this->createMock(\Magento\VisualMerchandiser\Model\Category\Products::class);
        $products
            ->expects($this->atLeastOnce())
            ->method('getCollectionForGrid')
            ->willReturn($this->collection);

        $category = $this->createMock(\Magento\Catalog\Model\Category::class);
        $category
            ->expects($this->any())
            ->method('getProductsPosition')
            ->willReturn(['a' => 'b']);

        $coreRegistry = $this->createMock(\Magento\Framework\Registry::class);
        $catalogImage = $this->createMock(\Magento\Catalog\Helper\Image::class);
        $backendHelper = $this->createMock(\Magento\Backend\Helper\Data::class);

        $this->tileBlock = $this->objectManager->getObject(
            \Magento\VisualMerchandiser\Block\Adminhtml\Category\Merchandiser\Tile::class,
            [
                'context' => $context,
                'backendHelper' => $backendHelper,
                'coreRegistry' => $coreRegistry,
                'catalogImage' => $catalogImage,
                'products' => $products
            ]
        );

        /** @var \Magento\Framework\View\LayoutInterface|\PHPUnit_Framework_MockObject_MockObject $layout */
        $layout = $this->createMock(\Magento\Framework\View\LayoutInterface::class);
        $layout
            ->expects($this->any())
            ->method('getParentName')
            ->willReturn('block');

        $block = $this->createPartialMock(\Magento\Framework\DataObject::class, ['_getPositionCacheKey']);
        $layout
            ->expects($this->any())
            ->method('getBlock')
            ->willReturn($block);

        $this->tileBlock->setLayout($layout);

        $this->tileBlock->setPositionCacheKey('xxxxxx');
    }

    /**
     * Tests if collection is returned and set from _prepareCollection
     */
    public function testPrepareCollection()
    {
        $this->tileBlock->setData('id', 1);
        $collection = $this->tileBlock->getPreparedCollection();
        $this->assertEquals($this->collection, $this->tileBlock->getCollection());
        $this->assertEquals($this->collection, $collection);
    }
}
