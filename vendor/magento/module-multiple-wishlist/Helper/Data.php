<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Helper;

/**
 * Multiple wishlist helper
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Magento\Wishlist\Helper\Data
{
    /**
     * The list of default wishlists grouped by customer id
     *
     * @var array
     */
    protected $_defaultWishlistsByCustomer = [];

    /**
     * Item collection factory
     *
     * @var \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory
     */
    protected $_itemCollectionFactory;

    /**
     * Wishlist collection factory
     *
     * @var \Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory
     */
    protected $_wishlistCollectionFactory;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlistFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Customer\Helper\View $customerViewHelper
     * @param \Magento\Wishlist\Controller\WishlistProviderInterface $wishlistProvider
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory $itemCollectionFactory
     * @param \Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory $wishlistCollectionFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Customer\Helper\View $customerViewHelper,
        \Magento\Wishlist\Controller\WishlistProviderInterface $wishlistProvider,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory $itemCollectionFactory,
        \Magento\Wishlist\Model\ResourceModel\Wishlist\CollectionFactory $wishlistCollectionFactory
    ) {
        $this->_itemCollectionFactory = $itemCollectionFactory;
        $this->_wishlistCollectionFactory = $wishlistCollectionFactory;
        parent::__construct(
            $context,
            $coreRegistry,
            $customerSession,
            $wishlistFactory,
            $storeManager,
            $postDataHelper,
            $customerViewHelper,
            $wishlistProvider,
            $productRepository
        );
    }

    /**
     * Create wishlist item collection
     *
     * @return \Magento\Wishlist\Model\ResourceModel\Item\Collection
     */
    protected function _createWishlistItemCollection()
    {
        if ($this->isMultipleEnabled()) {
            return $this->_itemCollectionFactory->create()->addCustomerIdFilter(
                $this->getCustomer()->getId()
            )->addStoreFilter(
                $this->_storeManager->getWebsite()->getStoreIds()
            )->setVisibilityFilter();
        } else {
            return parent::_createWishlistItemCollection();
        }
    }

    /**
     * Check whether multiple wishlist is enabled
     *
     * @return bool
     */
    public function isMultipleEnabled()
    {
        return $this->_moduleManager->isOutputEnabled($this->_getModuleName()) && $this->scopeConfig->getValue(
            'wishlist/general/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        ) && $this->scopeConfig->getValue(
            'wishlist/general/multiple_enabled',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check whether given wishlist is default for it's customer
     *
     * @param \Magento\Wishlist\Model\Wishlist $wishlist
     * @return bool
     */
    public function isWishlistDefault(\Magento\Wishlist\Model\Wishlist $wishlist)
    {
        return $this->getDefaultWishlist($wishlist->getCustomerId())->getId() == $wishlist->getId();
    }

    /**
     * Retrieve customer's default wishlist
     *
     * @param int $customerId
     * @return \Magento\Wishlist\Model\Wishlist
     */
    public function getDefaultWishlist($customerId = null)
    {
        if (!$customerId && $this->getCustomer()) {
            $customerId = $this->getCustomer()->getId();
        }
        if (!isset($this->_defaultWishlistsByCustomer[$customerId])) {
            $this->_defaultWishlistsByCustomer[$customerId] = $this->_wishlistFactory->create();
            $this->_defaultWishlistsByCustomer[$customerId]->loadByCustomerId($customerId, false);
        }
        return $this->_defaultWishlistsByCustomer[$customerId];
    }

    /**
     * Get max allowed number of wishlists per customers
     *
     * @return int
     */
    public function getWishlistLimit()
    {
        return $this->scopeConfig->getValue(
            'wishlist/general/multiple_wishlist_number',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check whether given wishlist collection size exceeds wishlist limit
     *
     * @param \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $wishlistList
     * @return bool
     */
    public function isWishlistLimitReached(\Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $wishlistList)
    {
        return count($wishlistList) >= $this->getWishlistLimit();
    }

    /**
     * Retrieve Wishlist collection by customer id
     *
     * @param int $customerId
     * @return \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection
     */
    public function getCustomerWishlists($customerId = null)
    {
        if (!$customerId && $this->getCustomer()) {
            $customerId = $this->getCustomer()->getId();
        }
        $wishlistsByCustomer = $this->_coreRegistry->registry('wishlists_by_customer');
        if (!isset($wishlistsByCustomer[$customerId])) {
            /** @var \Magento\Wishlist\Model\ResourceModel\Wishlist\Collection $collection */
            $collection = $this->_wishlistCollectionFactory->create();
            $collection->filterByCustomerId($customerId);
            if ($customerId && !$collection->getItems()) {
                $wishlist = $this->addWishlist($customerId);
                $collection->addItem($wishlist);
            }
            $wishlistsByCustomer[$customerId] = $collection;
            $this->_coreRegistry->register('wishlists_by_customer', $wishlistsByCustomer);
        }
        return $wishlistsByCustomer[$customerId];
    }

    /**
     * Create new wishlist
     *
     * @param int $customerId
     * @return \Magento\Wishlist\Model\Wishlist
     */
    protected function addWishlist($customerId)
    {
        $wishlist = $this->_wishlistFactory->create();
        $wishlist->setCustomerId($customerId);
        $wishlist->generateSharingCode();
        $wishlist->save();
        return $wishlist;
    }

    /**
     * Retrieve number of wishlist items in given wishlist
     *
     * @param \Magento\Wishlist\Model\Wishlist $wishlist
     * @return int
     */
    public function getWishlistItemCount(\Magento\Wishlist\Model\Wishlist $wishlist)
    {
        $collection = $wishlist->getItemCollection();
        if ($this->scopeConfig->getValue(
            self::XML_PATH_WISHLIST_LINK_USE_QTY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )
        ) {
            $count = $collection->getItemsQty();
        } else {
            $count = $collection->getSize();
        }
        return $count;
    }
}
