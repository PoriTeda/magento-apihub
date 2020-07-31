<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\MultipleWishlist\Controller;

use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\RequestInterface;

/**
 * Multiple wishlist frontend search controller
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class Search extends \Magento\Framework\App\Action\Action
{
    /**
     * Localization filter
     *
     * @var \Zend_Filter_LocalizedToNormalized
     */
    protected $_localFilter;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * Customer session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * Checkout cart
     *
     * @var \Magento\Checkout\Model\Cart
     */
    protected $_checkoutCart;

    /**
     * Checkout session
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $_checkoutSession;

    /**
     * Strategy name factory
     *
     * @var \Magento\MultipleWishlist\Model\Search\Strategy\NameFactory
     */
    protected $_strategyNameFactory;

    /**
     * Strategy email factory
     *
     * @var \Magento\MultipleWishlist\Model\Search\Strategy\EmailFactory
     */
    protected $_strategyEmailFactory;

    /**
     * Search factory
     *
     * @var \Magento\MultipleWishlist\Model\SearchFactory
     */
    protected $_searchFactory;

    /**
     * Wishlist factory
     *
     * @var \Magento\Wishlist\Model\WishlistFactory
     */
    protected $_wishlistFactory;

    /**
     * Item model factory
     *
     * @var \Magento\Wishlist\Model\ItemFactory
     */
    protected $_itemFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Wishlist\Model\ItemFactory $itemFactory
     * @param \Magento\Wishlist\Model\WishlistFactory $wishlistFactory
     * @param \Magento\MultipleWishlist\Model\SearchFactory $searchFactory
     * @param \Magento\MultipleWishlist\Model\Search\Strategy\EmailFactory $strategyEmailFactory
     * @param \Magento\MultipleWishlist\Model\Search\Strategy\NameFactory $strategyNameFactory
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Checkout\Model\Cart $checkoutCart
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Wishlist\Model\ItemFactory $itemFactory,
        \Magento\Wishlist\Model\WishlistFactory $wishlistFactory,
        \Magento\MultipleWishlist\Model\SearchFactory $searchFactory,
        \Magento\MultipleWishlist\Model\Search\Strategy\EmailFactory $strategyEmailFactory,
        \Magento\MultipleWishlist\Model\Search\Strategy\NameFactory $strategyNameFactory,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Checkout\Model\Cart $checkoutCart,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Framework\Module\Manager $moduleManager
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_itemFactory = $itemFactory;
        $this->_wishlistFactory = $wishlistFactory;
        $this->_searchFactory = $searchFactory;
        $this->_strategyEmailFactory = $strategyEmailFactory;
        $this->_strategyNameFactory = $strategyNameFactory;
        $this->_checkoutSession = $checkoutSession;
        $this->_checkoutCart = $checkoutCart;
        $this->_customerSession = $customerSession;
        $this->_localeResolver = $localeResolver;
        $this->moduleManager = $moduleManager;
        parent::__construct($context);
    }

    /**
     * Check if multiple wishlist is enabled on current store before all other actions
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->moduleManager->isEnabled('Magento_MultipleWishlist')) {
            throw new NotFoundException(__('Page not found.'));
        }
        return parent::dispatch($request);
    }
}
