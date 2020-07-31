<?php
namespace Riki\BackOrder\Model\AdvancedCheckout;

use Magento\AdvancedCheckout\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;

class Cart extends \Riki\Sales\Model\AdvancedCheckout\Cart
{
    protected $_backOrderHelper;

    public function __construct(
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Message\Factory $messageFactory,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        Data $checkoutData,
        \Magento\Catalog\Model\Product\OptionFactory $optionFactory,
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Catalog\Model\Product\CartConfiguration $productConfiguration,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockStateInterface $stockState,
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        ProductRepositoryInterface $productRepository,
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Riki\BackOrder\Helper\Admin $backOrderHelper,
        $itemFailedStatus = Data::ADD_ITEM_STATUS_FAILED_SKU,
        array $data = []
    ){

        $this->_backOrderHelper = $backOrderHelper;

        parent::__construct(
            $cart,
            $messageFactory,
            $eventManager,
            $checkoutData,
            $optionFactory,
            $wishlistFactory,
            $quoteRepository,
            $storeManager,
            $localeFormat,
            $messageManager,
            $productTypeConfig,
            $productConfiguration,
            $customerSession,
            $stockRegistry,
            $stockState,
            $stockHelper,
            $productRepository,
            $quoteFactory,
            $itemFailedStatus,
            $data
        );
    }

    /**
     * Add products previously successfully processed by prepareAddProductsBySku() to cart
     *
     * @param \Magento\Checkout\Model\Cart\CartInterface|null $cart Custom cart model (different from
     *                                                              checkout/cart)
     * @param bool $saveQuote Whether cart quote should be saved
     * @return $this
     */
    public function saveAffectedProducts(\Magento\Checkout\Model\Cart\CartInterface $cart = null, $saveQuote = true)
    {
        $cart = $cart ? $cart : $this->_cart;
        $affectedItems = $this->getAffectedItems();

        // validate back-order rule
        $requestItems = [];

        foreach($affectedItems as $affectedItem){

            if(isset($affectedItem['item']['id'])){
                if(!isset($requestItems[$affectedItem['item']['id']]))
                    $requestItems[$affectedItem['item']['id']] = 0;

                $requestItems[$affectedItem['item']['id']] += $affectedItem['item']['qty'];
            }
        }

        $validateResult = $this->_backOrderHelper->validateForAddAction($requestItems);

        if($validateResult !== true)
            $affectedItems = [];
        //

        foreach ($affectedItems as &$item) {
            if ($item['code'] == Data::ADD_ITEM_STATUS_SUCCESS) {
                $this->_safeAddProduct($item, $cart);
            }
        }
        $this->setAffectedItems($affectedItems);
        $this->removeSuccessItems();
        if ($saveQuote) {
            $cart->saveQuote();
        }
        return $this;
    }
}
