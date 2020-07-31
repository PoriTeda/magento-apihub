<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Test\Unit\Observer;

use Magento\GiftCardAccount\Model\Giftcardaccount;

class ProcessOrderPlaceTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\GiftCardAccount\Observer\ProcessOrderPlace */
    private $model;

    /**
     * @var \Magento\Framework\Event\Observer
     */
    private $observer;

    /**
     * @var \Magento\Framework\DataObject
     */
    private $event;

    /**
     * Gift card account giftcardaccount
     *
     * @var \Magento\GiftCardAccount\Model\GiftcardaccountFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    private $giftCAFactoryMock;

    /**
     * Gift card account data
     *
     * @var \Magento\GiftCardAccount\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    private $giftCAHelperMock = null;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);

        $contextMock = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->giftCAFactoryMock  = $this->getMockBuilder(\Magento\GiftCardAccount\Model\GiftcardaccountFactory::class)
            ->setMethods(['create', 'load', 'charge', 'setOrder', 'save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->giftCAHelperMock = $objectManagerHelper->getObject(
            \Magento\GiftCardAccount\Helper\Data::class,
            ['context' => $contextMock]
        );

        $this->model = $objectManagerHelper->getObject(
            \Magento\GiftCardAccount\Observer\ProcessOrderPlace::class,
            [
                'giftCAFactory' => $this->giftCAFactoryMock,
                'giftCAHelper' => $this->giftCAHelperMock,
            ]
        );

        $this->event = new \Magento\Framework\DataObject();

        $this->observer = new \Magento\Framework\Event\Observer(['event' => $this->event]);
    }

    /**
     * @param array $giftCards
     * @param float|int $giftCardsAmount
     * @param float|int $baseGiftCardsAmount
     * @dataProvider processOrderPlaceDataProvider
     */
    public function testProcessOrderPlace($giftCards, $giftCardsAmount, $baseGiftCardsAmount)
    {
        $giftCardsQuote = is_array($giftCards) ? json_encode($giftCards) : $giftCards;
        $order = new \Magento\Framework\DataObject();

        $quoteMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->setMethods(
                [
                    'getShippingAddress',
                    'getBillingAddress',
                    'isVirtual',
                    'getGiftCardsAmount',
                    'getBaseGiftCardsAmount',
                    'getTotals'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $quoteMock->expects($this->any())->method('isVirtual')->willReturn(false);
        $addressMock = $this->createPartialMock(
            \Magento\Quote\Model\Quote\Address::class,
            ['getGiftCardsAmount', 'getBaseGiftCardsAmount', 'getGiftCards']
        );
        $addressMock->expects($this->any())->method('getGiftCards')->willReturn($giftCardsQuote);
        $addressMock->expects($this->any())
            ->method('getGiftCardsAmount')
            ->will($this->returnValue($giftCardsAmount));
        $addressMock->expects($this->any())
            ->method('getBaseGiftCardsAmount')
            ->will($this->returnValue($baseGiftCardsAmount));

        $this->giftCAFactoryMock->expects($this->any())
            ->method('create')
            ->will($this->returnSelf());
        $this->giftCAFactoryMock->expects($this->any())
            ->method('load')
            ->will($this->returnSelf());
        $this->giftCAFactoryMock->expects($this->any())
            ->method('charge')
            ->will($this->returnSelf());
        $this->giftCAFactoryMock->expects($this->any())
            ->method('setOrder')
            ->will($this->returnSelf());
        $this->giftCAFactoryMock->expects($this->any())
            ->method('save')
            ->will($this->returnSelf());

        $this->event->setOrder($order);
        $this->event->setQuote($quoteMock);
        $this->event->setAddress($addressMock);
        $this->model->execute($this->observer);

        $this->assertEquals($giftCardsAmount, $order->getGiftCardsAmount());
        $this->assertEquals($baseGiftCardsAmount, $order->getBaseGiftCardsAmount());
    }

    /**
     * @case 1 POSITIVE we try to send array of giftCards data and baseGiftCardsAmount (integer)
     * @case 2 POSITIVE we try to send empty array of giftCards data and baseGiftCardsAmount (float)
     * @case 3 POSITIVE we try to send null  giftCards  and null baseGiftCardsAmount
     *
     * @return array
     */
    public function processOrderPlaceDataProvider()
    {
        return [
            [
                [
                    [
                        Giftcardaccount::ID => "5",
                        Giftcardaccount::CODE => '0AIMPAGVTAUQ',
                        Giftcardaccount::AMOUNT => 100,
                        Giftcardaccount::BASE_AMOUNT => "100.0000"
                    ],
                    [
                        Giftcardaccount::ID => "6",
                        Giftcardaccount::CODE => 'GVTAUQ0AIMPA',
                        Giftcardaccount::AMOUNT => 200,
                        Giftcardaccount::BASE_AMOUNT => "200.0000"
                    ],
                ],
                300,
                300
            ],
            [[], 0.5, 0.5],
            [null, null, null],
        ];
    }
}
