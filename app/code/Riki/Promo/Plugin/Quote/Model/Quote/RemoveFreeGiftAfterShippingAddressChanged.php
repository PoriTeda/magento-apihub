<?php
namespace Riki\Promo\Plugin\Quote\Model\Quote;

class RemoveFreeGiftAfterShippingAddressChanged
{
    /**
     * @var \Riki\Promo\Helper\Item
     */
    protected $itemHelper;

    /**
     * @var \Riki\Promo\Helper\Cart
     */
    protected $cartHelper;

    /**
     * RemoveFreeGiftAfterShippingAddressChanged constructor.
     * @param \Riki\Promo\Helper\Item $itemHelper
     * @param \Riki\Promo\Helper\Cart $cartHelper
     */
    public function __construct(
        \Riki\Promo\Helper\Item $itemHelper,
        \Riki\Promo\Helper\Cart $cartHelper
    ) {
        $this->itemHelper = $itemHelper;
        $this->cartHelper = $cartHelper;
    }

    /**
     * Need to reset free gift items when change region to handle case lead time is inactive for new region
     *
     * @param \Magento\Quote\Model\Quote $quote
     * @param \Closure $proceed
     * @param \Magento\Quote\Api\Data\AddressInterface|null $address
     * @return mixed
     */
    public function aroundSetShippingAddress(
        \Magento\Quote\Model\Quote $quote,
        \Closure $proceed,
        \Magento\Quote\Api\Data\AddressInterface $address = null
    ) {
        $beforeRegionId = $quote->getShippingAddress()->getRegionId();

        $result = $proceed($address);

        if ($address) {
            if ($beforeRegionId != $address->getRegionId()) {
                /** @var \Magento\Quote\Model\Quote\Item $item */
                foreach ($quote->getAllItems() as $item) {
                    if ($this->itemHelper->isPromoItem($item)) {
                        $availableQty = $this->cartHelper->checkAvailableQty(
                            $item->getProduct(),
                            $item->getQty(),
                            $quote
                        );

                        if ($availableQty < $item->getQty()) {
                            $quote->deleteItem($item);
                        }
                    }
                }

                $this->cartHelper->updateQuoteTotalQty(false, $quote);
            }
        }

        return $result;
    }
}
