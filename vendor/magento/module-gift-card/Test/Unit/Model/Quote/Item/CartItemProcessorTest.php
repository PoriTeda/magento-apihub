<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftCard\Test\Unit\Model\Quote\Item;

class CartItemProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $objectFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataObjHelperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $gcFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $prodOptFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $extFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $cartItemMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $extensionAttributeMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $productOptionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $giftCardOptionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $optionMock;

    /**
     * @var \Magento\GiftCard\Model\Quote\Item\CartItemProcessor
     */
    protected $model;

    protected function setUp()
    {
        $this->objectFactoryMock =
            $this->createPartialMock(\Magento\Framework\DataObject\Factory::class, ['create']);
        $this->dataObjHelperMock = $this->createMock(\Magento\Framework\Api\DataObjectHelper::class);
        $this->gcFactoryMock =
            $this->createPartialMock(\Magento\GiftCard\Model\Giftcard\OptionFactory::class, ['create']);
        $this->prodOptFactoryMock =
            $this->createPartialMock(\Magento\Quote\Model\Quote\ProductOptionFactory::class, ['create']);
        $this->extFactoryMock =
            $this->createPartialMock(\Magento\Quote\Api\Data\ProductOptionExtensionFactory::class, ['create']);

        $this->extensionAttributeMock =
            $this->createPartialMock(
                \Magento\Quote\Api\Data\ProductOptionExtension::class,
                ['getGiftcardItemOption', 'setGiftcardItemOption']
            );
        $this->productOptionMock = $this->createMock(\Magento\Quote\Api\Data\ProductOptionInterface::class);
        $this->giftCardOptionMock = $this->createMock(\Magento\GiftCard\Model\Giftcard\Option::class);
        $this->cartItemMock = $this->createMock(\Magento\Quote\Model\Quote\Item::class);
        $this->optionMock =
            $this->createPartialMock(\Magento\Quote\Model\Quote\Item\Option::class, ['getCode', 'getValue']);
        $this->model = new \Magento\GiftCard\Model\Quote\Item\CartItemProcessor(
            $this->objectFactoryMock,
            $this->dataObjHelperMock,
            $this->gcFactoryMock,
            $this->prodOptFactoryMock,
            $this->extFactoryMock
        );
    }

    public function testConvertToBuyRequest()
    {
        $requestData = [
            "giftcard_amount" => "custom",
            "custom_giftcard_amount" => 7,
        ];
        $this->cartItemMock
            ->expects($this->once())
            ->method('getProductOption')
            ->willReturn($this->productOptionMock);
        $this->productOptionMock
            ->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->extensionAttributeMock
            ->expects($this->any())
            ->method('getGiftcardItemOption')
            ->willReturn($this->giftCardOptionMock);
        $this->giftCardOptionMock->expects($this->once())->method('getData')->willReturn($requestData);
        $this->objectFactoryMock->expects($this->once())->method('create')->with($requestData);
        $this->model->convertToBuyRequest($this->cartItemMock);
    }

    public function testConvertToBuyRequestWhenProductOptionNotExist()
    {
        $this->cartItemMock
            ->expects($this->once())
            ->method('getProductOption')
            ->willReturn(null);
        $this->objectFactoryMock->expects($this->never())->method('create');
        $this->model->convertToBuyRequest($this->cartItemMock);
    }

    public function testProcessProductOptions()
    {
        $this->cartItemMock->expects($this->once())->method('getOptions')->willReturn([$this->optionMock]);
        $this->optionMock->expects($this->once())->method('getCode')->willReturn('giftcard_amount');
        $this->optionMock->expects($this->once())->method('getValue')->willReturn(10);
        $this->gcFactoryMock->expects($this->once())->method('create')->willReturn($this->giftCardOptionMock);
        $this->dataObjHelperMock
            ->expects($this->once())
            ->method('populateWithArray')
            ->with($this->giftCardOptionMock, ['giftcard_amount'=> 10])
            ->willReturn($this->giftCardOptionMock);
        $this->cartItemMock
            ->expects($this->exactly(2))
            ->method('getProductOption')
            ->willReturn($this->productOptionMock);
        $this->productOptionMock
            ->expects($this->exactly(2))
            ->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributeMock);
        $this->extensionAttributeMock
            ->expects($this->once())
            ->method('setGiftcardItemOption')
            ->with($this->giftCardOptionMock);
        $this->productOptionMock
            ->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock);
        $this->cartItemMock->expects($this->once())->method('setProductOption')->with($this->productOptionMock);

        $this->assertEquals($this->cartItemMock, $this->model->processOptions($this->cartItemMock));
    }

    public function testProcessProductOptionsWhenExtensibleAttributeNotExist()
    {
        $this->cartItemMock->expects($this->once())->method('getOptions')->willReturn([$this->optionMock]);
        $this->optionMock->expects($this->once())->method('getCode')->willReturn('giftcard_amount');
        $this->optionMock->expects($this->once())->method('getValue')->willReturn(10);
        $this->gcFactoryMock->expects($this->once())->method('create')->willReturn($this->giftCardOptionMock);
        $this->dataObjHelperMock
            ->expects($this->once())
            ->method('populateWithArray')
            ->with($this->giftCardOptionMock, ['giftcard_amount'=> 10])
            ->willReturn($this->giftCardOptionMock);
        $this->cartItemMock
            ->expects($this->once())
            ->method('getProductOption')
            ->willReturn(null);
        $this->prodOptFactoryMock->expects($this->once())->method('create')->willReturn($this->productOptionMock);
        $this->productOptionMock
            ->expects($this->once())
            ->method('getExtensionAttributes')
            ->willReturn(null);
        $this->extFactoryMock->expects($this->once())->method('create')->willReturn($this->extensionAttributeMock);
        $this->extensionAttributeMock
            ->expects($this->once())
            ->method('setGiftcardItemOption')
            ->with($this->giftCardOptionMock);
        $this->productOptionMock
            ->expects($this->once())
            ->method('setExtensionAttributes')
            ->with($this->extensionAttributeMock);
        $this->cartItemMock->expects($this->once())->method('setProductOption')->with($this->productOptionMock);
        $this->assertEquals($this->cartItemMock, $this->model->processOptions($this->cartItemMock));
    }

    public function testProcessProductOptionsWhenOptionsNotExists()
    {
        $this->cartItemMock->expects($this->once())->method('getOptions')->willReturn(null);
        $this->dataObjHelperMock
            ->expects($this->never())
            ->method('populateWithArray');
        $this->assertEquals($this->cartItemMock, $this->model->processOptions($this->cartItemMock));
    }
}
