<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
// @codingStandardsIgnoreStart
namespace Magento\PricePermissions\Test\Unit\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler\ProductType;

use \Magento\PricePermissions\Controller\Adminhtml\Product\Initialization\Helper\Plugin\Handler\ProductType\Downloadable;
// @codingStandardsIgnoreEnd

class DownloadableTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Downloadable
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productMock;

    protected function setUp()
    {
        $this->productMock = $this->createPartialMock(
            \Magento\Catalog\Model\Product::class,
            ['getDownloadableData', 'getTypeInstance', 'setDownloadableData', 'getTypeId', '__wakeup']
        );
        $this->model = new Downloadable();
    }

    public function testHandleWithNonDownloadableProductType()
    {
        $this->productMock->expects($this->once())->method('getTypeId')->will($this->returnValue('some product type'));
        $this->productMock->expects($this->never())->method('getDownloadableData');
        $this->model->handle($this->productMock);
    }

    public function testHandleWithoutDownloadableLinks()
    {
        $this->productMock->expects(
            $this->once()
        )->method(
            'getTypeId'
        )->will(
            $this->returnValue(\Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE)
        );
        $this->productMock->expects($this->once())->method('getDownloadableData')->will($this->returnValue([]));

        $this->productMock->expects($this->never())->method('setDownloadableData');
        $this->model->handle($this->productMock);
    }

    public function testHandleWithoutDownloadableData()
    {
        $this->productMock->expects(
            $this->once()
        )->method(
            'getTypeId'
        )->will(
            $this->returnValue(\Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE)
        );
        $this->productMock->expects($this->once())->method('getDownloadableData')->will($this->returnValue(null));

        $this->productMock->expects($this->never())->method('setDownloadableData');
        $this->model->handle($this->productMock);
    }

    public function testHandleWithDownloadableData()
    {
        $linkMock = $this->createPartialMock(\Magento\Downloadable\Model\Link::class, ['getPrice', '__wakeup']);
        $linkMock->expects($this->any())->method('getPrice')->will($this->returnValue(100500));
        $links = ['1' => $linkMock, '2' => $linkMock];
        $downloadableData = [
            'link' => [
                ['link_id' => 1, 'is_delete' => false],
                ['link_id' => 2, 'is_delete' => true],
                ['link_id' => 3, 'is_delete' => false],
            ],
        ];
        $expected = [
            'link' => [
                ['link_id' => 1, 'is_delete' => false, 'price' => 100500],
                ['link_id' => 2, 'is_delete' => true, 'price' => 0],
                ['link_id' => 3, 'is_delete' => false, 'price' => 0],
            ],
        ];

        $this->productMock->expects(
            $this->once()
        )->method(
            'getTypeId'
        )->will(
            $this->returnValue(\Magento\Downloadable\Model\Product\Type::TYPE_DOWNLOADABLE)
        );
        $this->productMock->expects(
            $this->once()
        )->method(
            'getDownloadableData'
        )->will(
            $this->returnValue($downloadableData)
        );

        $typeMock = $this->createMock(\Magento\Downloadable\Model\Product\Type::class);
        $typeMock->expects($this->once())->method('getLinks')->will($this->returnValue($links));
        $this->productMock->expects($this->once())->method('getTypeInstance')->will($this->returnValue($typeMock));

        $this->productMock->expects($this->once())->method('setDownloadableData')->with($expected);
        $this->model->handle($this->productMock);
    }
}
