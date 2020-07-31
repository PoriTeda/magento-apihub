<?php
namespace Riki\Sales\Model\AdvancedCheckout;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;

/**
 * Admin Checkout processing model
 *
 * @method bool hasErrorMessage()
 * @method string getErrorMessage()
 * @method setErrorMessage(string $message)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Cart extends \Magento\AdvancedCheckout\Model\Backend\Cart
{
    /**
     * Safely add product to cart, revert cart in error case
     *
     * @param array &$item
     * @param \Magento\Checkout\Model\Cart\CartInterface $cart If we need to add product to different cart from
     *                                                         checkout/cart
     * @param bool $suppressSuperMode
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _safeAddProduct(
        &$item,
        \Magento\Checkout\Model\Cart\CartInterface $cart,
        $suppressSuperMode = false
    ) {
        $quote = $cart->getQuote();

        $success = true;
        $skipCheckQty = !$suppressSuperMode
            && $this->_isCheckout()
            && !$this->_isFrontend()
            && empty($item['item']['is_qty_disabled'])
            && !$cart->getQuote()->getIsSuperMode();
        if ($skipCheckQty) {
            $cart->getQuote()->setIsSuperMode(true);
        }

        try {
            $config = $this->getAffectedItemConfig($item['item']['sku']);
            if (!empty($config)) {
                $config['qty'] = $item['item']['qty'];
            } else {
                // If second parameter of addProduct() is not an array than it is considered to be qty
                $config = $item['item']['qty'];
            }
            $cart->addProduct($item['item']['id'], $config);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if (!$suppressSuperMode) {
                $success = false;
                $item['code'] = Data::ADD_ITEM_STATUS_FAILED_UNKNOWN;
                if ($this->_isFrontend()) {
                    $item['item']['error'] = $e->getMessage();
                } else {
                    $item['error'] = $e->getMessage();
                }
            }
        } catch (\Exception $e) {
            $success = false;
            $item['code'] = Data::ADD_ITEM_STATUS_FAILED_UNKNOWN;
            $error = __('We can\'t add the item to your cart.');
            if ($this->_isFrontend()) {
                $item['item']['error'] = $error;
            } else {
                $item['error'] = $error;
            }
        }
        if ($skipCheckQty) {
            $cart->getQuote()->setIsSuperMode(false);
            if ($success) {
                $cart->setQuote($quote);
                // we need add products with checking their stock qty
                return $this->_safeAddProduct($item, $cart, true);
            }
        }

        $cart->setQuote($quote);
        return $this;
    }
}
