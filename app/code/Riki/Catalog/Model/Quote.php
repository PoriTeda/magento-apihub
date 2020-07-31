<?php

namespace Riki\Catalog\Model;

class Quote extends \Magento\Quote\Model\Quote
{
    /**
     * Advanced func to add product to quote - processing mode can be specified there.
     * Returns error message if product type instance can't prepare product.
     *
     * @param mixed $product
     * @param null|float|\Magento\Framework\DataObject $request
     * @param null|string $processMode
     * @return \Magento\Quote\Model\Quote\Item|string
     * @throws \Magento\Framework\Exception\LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function addProduct(
        \Magento\Catalog\Model\Product $product,
        $request = null,
        $processMode = \Magento\Catalog\Model\Product\Type\AbstractType::PROCESS_MODE_FULL
    ) {
        /*do not need to apply this logic for subscription order*/
        if ($this->getData('riki_course_id')) {
            return parent::addProduct($product, $request, $processMode);
        }

        /*do not need to validate first quote item*/
        if (!$this->getAllItems()) {
            return parent::addProduct($product, $request, $processMode);
        }

        /*do not need to validate free gift item*/
        if ($product->getData('ampromo_rule_id')) {
            return parent::addProduct($product, $request, $processMode);
        }

        /*product add from subscription course*/
        if ($product->getData('is_subscription_product')) {
            return parent::addProduct($product, $request, $processMode);
        }

        /*is out of stock product*/
        if ($product->getIsOosProduct()) {
            return parent::addProduct($product, $request, $processMode);
        }

        $isExistInCart = false;
        $isExistInCartId = null;

        $isExistNotAllowedOrderedWithOtherProduct = false;

        $errorMessage = '';

        foreach ($this->getAllItems() as $item) {
            /*do not need validate bundle children item*/
            if (!empty($item->getParentItemId()) || !empty($item->getParentItem())) {
                continue;
            }

            /*do not need to check promotion item*/
            if ($this->isPromotionItem($item)) {
                continue;
            }

            $productItem = $item->getProduct();

            if ($productItem) {
                if ($productItem->getId() == $product->getId()) {
                    $isExistInCart = true;
                    $isExistInCartId = $item->getId();
                    break;
                }

                $orderedWithOtherProductFlg = $productItem->getData('ordered_with_other_product_flg');
                if (!$orderedWithOtherProductFlg) {
                    $isExistNotAllowedOrderedWithOtherProduct = true;
                    $errorMessage = __('The product %1 cannot add to cart because the cart already contained product %2 cannot order with other products.', $product->getName(), $productItem->getName());
                }
            }
        }

        if ($isExistInCart) {
            if($request->getDataByKey('product_addtocart_detail_page')) {
                $this->removeItem($isExistInCartId);
            }
            return parent::addProduct($product, $request, $processMode);
        }

        $canOrderedWithOtherProduct = 0;

        if ($product->getCustomAttribute('ordered_with_other_product_flg')) {
            $canOrderedWithOtherProduct = $product->getCustomAttribute('ordered_with_other_product_flg')->getValue();
        }

        /*current product not allowed to ordered with other product*/
        if ($canOrderedWithOtherProduct == 0) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('The product %1 cannot order with other products.', $product->getName())
            );
        }

        /*cart has a product which not allowed to ordered with other product*/
        if ($isExistNotAllowedOrderedWithOtherProduct) {
            throw new \Magento\Framework\Exception\LocalizedException($errorMessage);
        }

        return parent::addProduct($product, $request, $processMode);
    }

    /**
     * Quote item is promotion item
     *
     * @param $quoteItem
     * @return bool
     */
    public function isPromotionItem($quoteItem)
    {
        if (!$quoteItem || !$quoteItem->getId()) {
            return false;
        }

        $buyRequest = $quoteItem->getBuyRequest();
        /*do not need to check promotion item*/
        if (isset($buyRequest['options']['ampromo_rule_id'])) {
            return true;
        }

        return false;
    }

    /**
     * Can place order with quote info
     *      Logic: cart can not contained multi product with difference ordered_with_other_product_flg attribute
     *
     * @return array
     */
    public function canPlaceOrder()
    {
        /*do not need to apply this logic for subscription order*/
        if ($this->getData('riki_course_id')) {
            return ['error' => false, 'message' => ''];
        }

        $allowedOtherProduct = 0;
        $notAllowedOtherProduct = 0;

        $notAllowedOtherProductName = '';

        foreach ($this->getAllItems() as $item) {
            if (!empty($item->getParentItemId())) {
                continue;
            }

            /*do not need to check promotion item*/
            if ($this->isPromotionItem($item)) {
                continue;
            }

            $productItem = $item->getProduct();

            if ($productItem) {
                $orderedWithOtherProductFlg = $productItem->getData('ordered_with_other_product_flg');

                if ($orderedWithOtherProductFlg) {
                    $allowedOtherProduct++;
                } else {
                    $notAllowedOtherProduct++;
                    $notAllowedOtherProductName = $productItem->getName();
                }
            }
        }

        $validate = true;

        if ($allowedOtherProduct && $notAllowedOtherProduct) {
            $validate = false;
        }

        if (!$allowedOtherProduct) {
            if ($notAllowedOtherProduct > 1) {
                $validate = false;
            }
        }

        if (!$validate) {
            return ['error' => true, 'message' => __('Your cart contained product %1 cannot order with other products.', $notAllowedOtherProductName)];
        }

        return ['error' => false, 'message' => ''];
    }

    /**
     * Get customer shipping address
     *      currently, only exists for case profile shipping address is used stock point address
     *      (for this case, customer shipping address is additional data for order detail)
     *
     * @return \Magento\Quote\Model\Quote\Address
     */
    public function getCustomerShippingAddress()
    {
        return $this->_getAddressByType(
            \Riki\Quote\Model\Quote\Address::ADDRESS_TYPE_CUSTOMER
        );
    }

    /**
     * Set customer shipping address
     *
     * @param \Magento\Quote\Api\Data\AddressInterface|null $address
     * @return $this
     */
    public function setCustomerShippingAddress(\Magento\Quote\Api\Data\AddressInterface $address = null)
    {
        $this->addAddress(
            $address->setAddressType(
                \Riki\Quote\Model\Quote\Address::ADDRESS_TYPE_CUSTOMER
            )
        );

        return $this;
    }
}
