<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\GiftWrapping\Plugin;

use Magento\GiftMessage\Api\Data\MessageInterface;
use Magento\GiftMessage\Api\ItemRepositoryInterface;
use Magento\GiftWrapping\Helper\Data;
use Magento\GiftWrapping\Model\WrappingFactory;

class MessageItemRepository
{
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var WrappingFactory
     */
    protected $wrappingFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     * @param WrappingFactory $wrappingFactory
     * @param Data $helper
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        WrappingFactory $wrappingFactory,
        Data $helper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->wrappingFactory = $wrappingFactory;
        $this->helper = $helper;
    }

    /**
     * Set gift wrapping from message for cart item
     *
     * @param ItemRepositoryInterface $subject
     * @param callable $proceed
     * @param int $cartId
     * @param MessageInterface $giftMessage
     * @param int $itemId
     * @return bool
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSave(
        ItemRepositoryInterface $subject,
        \Closure $proceed,
        $cartId,
        MessageInterface $giftMessage,
        $itemId
    ) {
        $proceed($cartId, $giftMessage, $itemId);

        $quote = $this->quoteRepository->getActive($cartId);
        $extensionAttributes = $giftMessage->getExtensionAttributes();
        if ($extensionAttributes && $this->helper->isGiftWrappingAvailableForItems()) {
            $wrappingId = $extensionAttributes->getWrappingId();
            $wrapping = $this->wrappingFactory->create()->load($wrappingId);
            $item = $quote->getItemById($itemId);
            $item->setGwId($wrapping->getId())
                ->setGiftCode($wrapping->getGiftCode())
                ->setSapCode($wrapping->getSapCode())
                ->setGiftWrapping($wrapping->getGiftName())
                ->save();
        }

        return true;
    }
}
