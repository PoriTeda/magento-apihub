<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PricePermissions\Test\Unit\Model\Entity\Attribute\Backend\Plugin;

use Magento\PricePermissions\Model\Entity\Attribute\Backend\Plugin\AbstractBackend as AbstractBackendPlugin;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\PricePermissions\Observer\ObserverData;
use Magento\Eav\Model\Entity\Attribute\Backend\AbstractBackend as EavAbstractBackend;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute as AbstractEavAttribute;

class AbstractBackendTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var AbstractBackendPlugin
     */
    private $plugin;

    /**
     * @var ObjectManagerHelper
     */
    private $objectManagerHelper;

    /**
     * @var ObserverData|\PHPUnit_Framework_MockObject_MockObject
     */
    private $observerDataMock;

    /**
     * @var EavAbstractBackend|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    /**
     * @var ProductInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $productMock;

    /**
     * @var AbstractEavAttribute|\PHPUnit_Framework_MockObject_MockObject
     */
    private $eavAttributeMock;

    protected function setUp()
    {
        $this->observerDataMock = $this->getMockBuilder(ObserverData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectMock = $this->getMockBuilder(EavAbstractBackend::class)
            ->disableOriginalConstructor()
            ->setMethods(['getAttribute'])
            ->getMockForAbstractClass();
        $this->productMock = $this->getMockBuilder(ProductInterface::class)
            ->setMethods(['isObjectNew', 'setData'])
            ->getMockForAbstractClass();
        $this->eavAttributeMock = $this->getMockBuilder(AbstractEavAttribute::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subjectMock->expects(static::any())
            ->method('getAttribute')
            ->willReturn($this->eavAttributeMock);

        $this->objectManagerHelper = new ObjectManagerHelper($this);
        $this->plugin = $this->objectManagerHelper->getObject(
            AbstractBackendPlugin::class,
            ['observerData' => $this->observerDataMock]
        );
    }

    public function testBeforeValidate()
    {
        $attributeCode = 'price_code';
        $defaultProductPriceString = '350';

        $this->observerDataMock->expects(static::atLeastOnce())
            ->method('isCanReadProductPrice')
            ->willReturn(false);
        $this->productMock->expects(static::atLeastOnce())
            ->method('isObjectNew')
            ->willReturn(true);
        $this->eavAttributeMock->expects(static::atLeastOnce())
            ->method('getFrontendInput')
            ->willReturn('price');
        $this->eavAttributeMock->expects(static::atLeastOnce())
            ->method('getAttributeCode')
            ->willReturn($attributeCode);
        $this->observerDataMock->expects(static::atLeastOnce())
            ->method('getDefaultProductPriceString')
            ->willReturn($defaultProductPriceString);
        $this->productMock->expects(static::once())
            ->method('setData')
            ->with($attributeCode, $defaultProductPriceString)
            ->willReturnSelf();

        $this->plugin->beforeValidate($this->subjectMock, $this->productMock);
    }

    public function testBeforeValidateNotProduct()
    {
        $this->observerDataMock->expects(static::any())
            ->method('isCanReadProductPrice')
            ->willReturn(false);
        $this->productMock->expects(static::never())
            ->method('isObjectNew');
        $this->eavAttributeMock->expects(static::any())
            ->method('getFrontendInput')
            ->willReturn('price');
        $this->productMock->expects(static::never())
            ->method('setData');

        $this->plugin->beforeValidate($this->subjectMock, 'string');
    }

    public function testBeforeValidateCanRead()
    {
        $this->observerDataMock->expects(static::atLeastOnce())
            ->method('isCanReadProductPrice')
            ->willReturn(true);
        $this->productMock->expects(static::any())
            ->method('isObjectNew')
            ->willReturn(true);
        $this->eavAttributeMock->expects(static::any())
            ->method('getFrontendInput')
            ->willReturn('price');
        $this->productMock->expects(static::never())
            ->method('setData');

        $this->plugin->beforeValidate($this->subjectMock, $this->productMock);
    }

    public function testBeforeValidateNotNew()
    {
        $this->observerDataMock->expects(static::any())
            ->method('isCanReadProductPrice')
            ->willReturn(false);
        $this->productMock->expects(static::atLeastOnce())
            ->method('isObjectNew')
            ->willReturn(false);
        $this->eavAttributeMock->expects(static::any())
            ->method('getFrontendInput')
            ->willReturn('price');
        $this->productMock->expects(static::never())
            ->method('setData');

        $this->plugin->beforeValidate($this->subjectMock, $this->productMock);
    }

    public function testBeforeValidateNotPrice()
    {
        $this->observerDataMock->expects(static::any())
            ->method('isCanReadProductPrice')
            ->willReturn(false);
        $this->productMock->expects(static::any())
            ->method('isObjectNew')
            ->willReturn(true);
        $this->eavAttributeMock->expects(static::atLeastOnce())
            ->method('getFrontendInput')
            ->willReturn('weight');
        $this->productMock->expects(static::never())
            ->method('setData');

        $this->plugin->beforeValidate($this->subjectMock, $this->productMock);
    }
}
