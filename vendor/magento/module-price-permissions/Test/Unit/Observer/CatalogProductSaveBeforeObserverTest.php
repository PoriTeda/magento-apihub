<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\PricePermissions\Test\Unit\Observer;

class CatalogProductSaveBeforeObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\PricePermissions\Observer\CatalogProductSaveBeforeObserver
     */
    protected $_observer;

    /**
     * @var \Magento\Framework\Event\Observer
     */
    protected $_varienObserver;

    /**
     * @var \Magento\Backend\Block\Widget\Grid\Extended
     */
    protected $_block;

    /**
     * @var \Magento\PricePermissions\Observer\ObserverData|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $observerData;

    protected function setUp()
    {
        $this->_block = $this->createPartialMock(
            \Magento\Backend\Block\Widget\Grid::class,
            [
                'getNameInLayout',
                'getMassactionBlock',
                'setCanReadPrice',
                'setCanEditPrice',
                'setTabData',
                'getChildBlock',
                'getParentBlock',
                'setDefaultProductPrice',
                'getForm',
                'getGroup',
            ]
        );
        $this->_varienObserver = $this->createPartialMock(
            \Magento\Framework\Event\Observer::class,
            ['getBlock', 'getEvent']
        );
        $this->_varienObserver->expects($this->any())->method('getBlock')->will($this->returnValue($this->_block));
    }

    public function testCatalogProductSaveBefore()
    {
        $helper = $this->getMockBuilder(\Magento\PricePermissions\Helper\Data::class)->disableOriginalConstructor()
            ->setMethods(['getCanAdminEditProductStatus'])->getMock();
        $helper->expects($this->once())->method('getCanAdminEditProductStatus')->will($this->returnValue(false));

        $product = $this->getMockBuilder(\Magento\Catalog\Model\Product::class)->disableOriginalConstructor()
            ->setMethods(['isObjectNew', 'setStatus', 'setPrice'])->getMock();
        $product->expects($this->exactly(2))->method('isObjectNew')->will($this->returnValue(true));
        $product->expects($this->once())->method('setPrice')->with(100);
        $product->expects($this->once())->method('setStatus')
            ->with(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED)
            ->willReturnSelf();

        $event = $this->getMockBuilder(\Magento\Framework\Event::class)->disableOriginalConstructor()
            ->setMethods(['getDataObject'])->getMock();
        $event->expects($this->once())->method('getDataObject')->will($this->returnValue($product));
        $this->_varienObserver->expects($this->once())->method('getEvent')->will($this->returnValue($event));

        $this->observerData = $this->getMockBuilder(\Magento\PricePermissions\Observer\ObserverData::class)->setMethods(
            [
                'setCanEditProductStatus',
                'getDefaultProductPriceString',
                'isCanReadProductPrice',
                'isCanEditProductStatus'
            ]
        )->disableOriginalConstructor()
        ->getMock();

        $this->observerData->expects($this->once())->method('setCanEditProductStatus')->with(false);
        $this->observerData->expects($this->once())->method('isCanReadProductPrice')->willReturn(false);
        $this->observerData->expects($this->once())->method('isCanEditProductStatus')->willReturn(false);
        $this->observerData->expects($this->once())->method('getDefaultProductPriceString')->willReturn(100);

        /** @var \Magento\PricePermissions\Observer\CatalogProductSaveBeforeObserver $model */
        $model = (new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this))
            ->getObject(
                \Magento\PricePermissions\Observer\CatalogProductSaveBeforeObserver::class,
                [
                    'pricePermData' => $helper,
                    'observerData' => $this->observerData,
                ]
            );

        $model->execute($this->_varienObserver);
    }
}
