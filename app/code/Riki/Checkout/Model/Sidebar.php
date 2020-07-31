<?php

namespace Riki\Checkout\Model;

use Magento\Checkout\Helper\Data as HelperData;
use Magento\Checkout\Model\Cart;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Locale\ResolverInterface;
use Riki\DelayPayment\Helper\Data as DelayPaymentHelper;

class Sidebar extends \Magento\Checkout\Model\Sidebar
{
    /**
     * Sidebar constructor.
     * @param Cart $cart
     * @param HelperData $helperData
     * @param ResolverInterface $resolver
     */
    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        HelperData $helperData,
        ResolverInterface $resolver
    ) {
        parent::__construct($cart, $helperData, $resolver);
    }

    /**
     * Update quote item
     *
     * @param int $itemId
     * @param int $itemQty
     * @throws LocalizedException
     * @return $this
     */
    public function updateQuoteItem($itemId, $itemQty)
    {
        $itemData = [$itemId => ['qty' => $this->normalize($itemQty)]];
        $this->cart->updateItems($itemData);

        // Validate order total amount threshold
        $this->cart->setValidateMaxMinCourse(true);
        $this->cart->save();
        return $this;
    }

    /**
     * Remove quote item
     *
     * @param int $itemId
     * @throws LocalizedException
     * @return $this
     */
    public function removeQuoteItem($itemId)
    {
        $this->cart->removeItem($itemId);
        // Validate order total amount threshold
        $this->cart->setValidateMaxMinCourse(true);
        $this->cart->save();
        return $this;
    }
}
