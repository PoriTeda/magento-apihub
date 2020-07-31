<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Test\Unit\Model;

use Magento\GiftRegistry\Model\GiftRegistryConfigProvider;

class GiftRegistryConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\GiftRegistry\Helper\Data
     */
    protected $helper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Checkout\Model\Session
     */
    protected $session;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\GiftRegistry\Model\Entity
     */
    protected $entity;

    /**
     * @var \Magento\GiftRegistry\Model\GiftRegistryConfigProvider
     */
    protected $model;

    /**
     * Initialize testable object
     */
    protected function setUp()
    {
        $this->helper = $this->getMockBuilder(\Magento\GiftRegistry\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->session = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $entityFactory = $this->getMockBuilder(\Magento\GiftRegistry\Model\EntityFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->entity = $this->getMockBuilder(\Magento\GiftRegistry\Model\Entity::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getShippingAddress', 'loadByEntityItem'])
            ->getMock();

        $entityFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->entity);

        /**
         * @var $entityFactory \Magento\GiftRegistry\Model\EntityFactory
         */
        $this->model = new GiftRegistryConfigProvider(
            $this->helper,
            $this->session,
            $entityFactory
        );
    }

    /**
     * @test
     */
    public function testGetConfig()
    {
        $quoteId = 'quoteId#1';
        $entityId = 'entityId#1';
        $isShipping = true;
        $giftregistryItemId = 'getGiftregistryItemId#1';
        $available = true;

        $quote = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)->disableOriginalConstructor()->getMock();
        $quoteItem = $this->getMockBuilder(\Magento\Quote\Model\Quote\Item::class)
            ->setMethods(['getGiftregistryItemId', 'getId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->helper->expects($this->once())
            ->method('isEnabled')
            ->willReturn($available);
        $this->session->expects($this->once())
            ->method('getQuoteId')
            ->willReturn($quoteId);
        $this->session->expects($this->once())
            ->method('getQuote')
            ->willReturn($quote);
        $quote->expects($this->once())
            ->method('getItemsCollection')
            ->willReturn([$quoteItem]);
        $quoteItem->expects($this->once())
            ->method('getGiftregistryItemId')
            ->willReturn($giftregistryItemId);
        $this->entity->expects($this->once())
            ->method('loadByEntityItem')
            ->with($giftregistryItemId)
            ->willReturnSelf();
        $this->entity->expects($this->once())
            ->method('getId')
            ->willReturn($entityId);
        $this->entity->expects($this->once())
            ->method('getShippingAddress')
            ->willReturn($isShipping);
        $quoteItem->expects($this->any())
            ->method('getId')
            ->willReturn($quoteId);

        $this->assertEquals(
            [
                'giftRegistry' => [
                    'available' => $available,
                    'id' => $giftregistryItemId
                ]
            ],
            $this->model->getConfig()
        );
    }

    /**
     * @test
     */
    public function testGetConfigNegative()
    {
        $available = false;

        $this->helper->expects($this->once())
            ->method('isEnabled')
            ->willReturn($available);
        $this->session->expects($this->once())
            ->method('getQuoteId')
            ->willReturn(null);

        $this->assertEquals(
            [
                'giftRegistry' => [
                    'available' => $available,
                    'id' => false
                ]
            ],
            $this->model->getConfig()
        );
    }
}
