<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Model\Plugin;

use Magento\Quote\Model\Quote;
use Magento\GiftCardAccount\Model\Giftcardaccount as ModelGiftcardaccount;
use Magento\Framework\Pricing\PriceCurrencyInterface;

class TotalsCollector
{
    /**
     * Gift card account data
     *
     * @var \Magento\GiftCardAccount\Helper\Data
     */
    protected $giftCardAccountData;

    /**
     * Gift card account giftcardaccount
     *
     * @var \Magento\GiftCardAccount\Model\GiftcardaccountFactory
     */
    protected $giftCAFactory;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param \Magento\GiftCardAccount\Helper\Data $giftCardAccountData
     * @param \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCAFactory
     * @param PriceCurrencyInterface $priceCurrency
     */
    public function __construct(
        \Magento\GiftCardAccount\Helper\Data $giftCardAccountData,
        \Magento\GiftCardAccount\Model\GiftcardaccountFactory $giftCAFactory,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->giftCAFactory = $giftCAFactory;
        $this->giftCardAccountData = $giftCardAccountData;
        $this->priceCurrency = $priceCurrency;
    }

    /**
     * Reset quote reward point amount
     *
     * @param \Magento\Quote\Model\Quote\TotalsCollector $subject
     * @param Quote $quote
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeCollect(
        \Magento\Quote\Model\Quote\TotalsCollector $subject,
        Quote $quote
    ) {
        $quote->setBaseGiftCardsAmount(0);
        $quote->setGiftCardsAmount(0);

        $quote->setBaseGiftCardsAmountUsed(0);
        $quote->setGiftCardsAmountUsed(0);

        $baseAmount = 0;
        $amount = 0;
        $cards = $this->giftCardAccountData->getCards($quote);
        foreach ($cards as $k => &$card) {
            $model = $this->giftCAFactory->create()->load($card[ModelGiftcardaccount::ID]);
            if ($model->isExpired() || $model->getBalance() == 0) {
                unset($cards[$k]);
            } elseif ($model->getBalance() != $card[ModelGiftcardaccount::BASE_AMOUNT]) {
                $card[ModelGiftcardaccount::BASE_AMOUNT] = $model->getBalance();
            } else {
                $card[ModelGiftcardaccount::AMOUNT] = $this->priceCurrency->round(
                    $this->priceCurrency->convert(
                        $card[ModelGiftcardaccount::BASE_AMOUNT],
                        $quote->getStore()
                    )
                );
                $baseAmount += $card[ModelGiftcardaccount::BASE_AMOUNT];
                $amount += $card[ModelGiftcardaccount::AMOUNT];
            }
        }
        if (!empty($cards)) {
            $this->giftCardAccountData->setCards($quote, $cards);
        }

        $quote->setBaseGiftCardsAmount($baseAmount);
        $quote->setGiftCardsAmount($amount);
    }
}
