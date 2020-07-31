<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Test\Unit\Model\Total\Quote;

use Magento\GiftCardAccount\Model\Total\Quote\Giftcardaccount;
use Magento\GiftCardAccount\Model\Giftcardaccount as GiftcardaccountModel;

/**
 * Class GiftcardaccountTest
 */
class GiftcardaccountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Giftcardaccount
     */
    private $model;

    /**
     * @var \Magento\GiftCardAccount\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $giftCAHelperMock;

    /**
     * @var \Magento\GiftCardAccount\Model\GiftcardaccountFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $giftCAFactory;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceCurrency;

    /**
     * Initialize testable object
     */
    protected function setUp()
    {
        $this->giftCAHelperMock = $this->createMock(\Magento\GiftCardAccount\Helper\Data::class);
        $this->giftCAFactory = $this->getMockBuilder(\Magento\GiftCardAccount\Model\GiftcardaccountFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->priceCurrency = $this->createMock(\Magento\Framework\Pricing\PriceCurrencyInterface::class);
        $this->model = new Giftcardaccount(
            $this->giftCAHelperMock,
            $this->giftCAFactory,
            $this->priceCurrency
        );
    }

    public function testFetch()
    {
        /** @var \Magento\Quote\Model\Quote|\PHPUnit_Framework_MockObject_MockObject $quoteMock */
        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->setMethods(['getAddressesCollection'])
            ->disableOriginalConstructor()
            ->getMock();
        /** @var \Magento\Quote\Model\Quote\Address\Total|\PHPUnit_Framework_MockObject_MockObject $totalMock */
        $totalMock = $this->getMockBuilder(\Magento\Quote\Model\Quote\Address\Total::class)
            ->setMethods(['getGiftCardsAmount'])
            ->disableOriginalConstructor()
            ->getMock();
        $card = [
            GiftcardaccountModel::ID => "7",
            GiftcardaccountModel::CODE => 'GHTRPAGVTAUQ',
            GiftcardaccountModel::AMOUNT => 50,
            GiftcardaccountModel::BASE_AMOUNT => "50.0000"
        ];
        $totalMock->expects($this->once())->method('getGiftCardsAmount')
            ->willReturn($card[GiftcardaccountModel::AMOUNT]);
        $this->giftCAHelperMock->expects($this->once())->method('getCards')->with($totalMock)->willReturn([$card]);
        $result = $this->model->fetch($quoteMock, $totalMock);
        $this->assertEquals(-$card[GiftcardaccountModel::AMOUNT], $result['value']);
    }
}
