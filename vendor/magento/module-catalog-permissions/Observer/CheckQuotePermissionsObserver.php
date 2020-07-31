<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Observer;

use Magento\CatalogPermissions\App\ConfigInterface;
use Magento\CatalogPermissions\Helper\Data;
use Magento\CatalogPermissions\Model\Permission\Index;
use Magento\Customer\Model\Session;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Quote\Model\Quote;
use Magento\Framework\Event\ObserverInterface;

class CheckQuotePermissionsObserver implements ObserverInterface
{
    /**
     * Permissions cache for products in cart
     *
     * @var array
     */
    protected $_permissionsQuoteCache = [];

    /**
     * Permissions index instance
     *
     * @var Index
     */
    protected $_permissionIndex;

    /**
     * Customer session instance
     *
     * @var Session
     */
    protected $_customerSession;

    /**
     * Catalog permission helper
     *
     * @var Data
     */
    protected $_catalogPermData;

    /**
     * Permissions configuration instance
     *
     * @var ConfigInterface
     */
    protected $_permissionsConfig;

    /**
     * Constructor
     *
     * @param ConfigInterface $permissionsConfig
     * @param Session $customerSession
     * @param Index $permissionIndex
     * @param Data $catalogPermData
     */
    public function __construct(
        ConfigInterface $permissionsConfig,
        Session $customerSession,
        Index $permissionIndex,
        Data $catalogPermData
    ) {
        $this->_permissionsConfig = $permissionsConfig;
        $this->_customerSession = $customerSession;
        $this->_permissionIndex = $permissionIndex;
        $this->_catalogPermData = $catalogPermData;
    }

    /**
     * Checks permissions for all quote items
     *
     * @param EventObserver $observer
     * @return $this
     */
    public function execute(EventObserver $observer)
    {
        if (!$this->_permissionsConfig->isEnabled()) {
            return $this;
        }

        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getCart()->getQuote();
        $allQuoteItems = $quote->getAllItems();
        $this->_initPermissionsOnQuoteItems($quote);

        foreach ($allQuoteItems as $quoteItem) {
            /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
            if ($quoteItem->getParentItem()) {
                continue;
            }

            if ($quoteItem->getDisableAddToCart() && !$quoteItem->isDeleted()) {
                $quote->removeItem($quoteItem->getQuoteId());
                $quote->deleteItem($quoteItem);
                $quote->setHasError(
                    true
                )->addMessage(
                    __('You cannot add "%1" to the cart.', $quoteItem->getName())
                );
            }
        }

        return $this;
    }

    /**
     * Initialize permissions for quote items
     *
     * @param Quote $quote
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _initPermissionsOnQuoteItems(Quote $quote)
    {
        $productIds = [];

        foreach ($quote->getAllItems() as $item) {
            if (!isset($this->_permissionsQuoteCache[$item->getProductId()]) && $item->getProductId()) {
                $productIds[] = $item->getProductId();
            }
        }

        if (!empty($productIds)) {
            $this->_permissionsQuoteCache += $this->_permissionIndex->getIndexForProduct(
                $productIds,
                $this->_customerSession->getCustomerGroupId(),
                $quote->getStoreId()
            );

            foreach ($productIds as $productId) {
                if (!isset($this->_permissionsQuoteCache[$productId])) {
                    $this->_permissionsQuoteCache[$productId] = false;
                }
            }
        }

        $defaultGrants = [
            'grant_catalog_category_view' => $this->_catalogPermData->isAllowedCategoryView(),
            'grant_catalog_product_price' => $this->_catalogPermData->isAllowedProductPrice(),
            'grant_checkout_items' => $this->_catalogPermData->isAllowedCheckoutItems()
        ];

        foreach ($quote->getAllItems() as $item) {
            if ($item->getProductId()) {
                $permission = $this->_permissionsQuoteCache[$item->getProductId()];
                if (!$permission && in_array(false, $defaultGrants)) {
                    // If no permission found, and no one of default grant is disallowed
                    $item->setDisableAddToCart(true);
                    continue;
                }

                foreach ($defaultGrants as $grant => $defaultPermission) {
                    if ($permission[$grant] == -2 || $permission[$grant] != -1 && !$defaultPermission) {
                        $item->setDisableAddToCart(true);
                        break;
                    }
                }
            }
        }

        return $this;
    }
}
