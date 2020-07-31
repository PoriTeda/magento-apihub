<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Test\Unit\Model;

use Magento\GiftCardAccount\Model\GiftCardConfigProvider;

class GiftCardConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GiftCardConfigProvider
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface
     */
    protected $management;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Checkout\Model\Session
     */
    protected $session;

    /**
     * Initialize testable object
     */
    protected function setUp()
    {
        $this->session = $this->getMockBuilder(\Magento\Checkout\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->management = $this->getMockBuilder(
            \Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface::class
        )->getMock();

        $this->model = new GiftCardConfigProvider(
            $this->management,
            $this->session
        );
    }

    /**
     * @test
     */
    public function testGetConfig()
    {
        $quoteId = 'quoteId#1';
        $giftCards = ['giftCard1', 'giftCard1'];
        $amount = 12.34;
        $giftCard = $this->getMockBuilder(\Magento\GiftCardAccount\Api\Data\GiftCardAccountInterface::class)->getMock();

        $this->session->expects($this->once())
            ->method('getQuoteId')
            ->willReturn($quoteId);
        $this->management->expects($this->once())
            ->method('getListByQuoteId')
            ->with($quoteId)
            ->willReturn($giftCard);
        $giftCard->expects($this->any())
            ->method('getGiftCards')
            ->willReturn($giftCards);
        $giftCard->expects($this->any())
            ->method('getGiftCardsAmountUsed')
            ->willReturn($amount);

        $this->assertEquals(
            [
                'payment' => [
                    'giftCardAccount' => [
                        'hasUsage' => true,
                        'amount'   => $amount,
                        'cards'    => $giftCards,
                        'available_amount' => null
                    ]
                ]
            ],
            $this->model->getConfig()
        );
    }
}
