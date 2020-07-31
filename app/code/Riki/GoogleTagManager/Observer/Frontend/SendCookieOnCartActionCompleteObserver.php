<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\GoogleTagManager\Observer\Frontend;


class SendCookieOnCartActionCompleteObserver extends \Magento\GoogleTagManager\Observer\SendCookieOnCartActionCompleteObserver
{
    /**
     * @var \Magento\GoogleTagManager\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $session;

    /**
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(
        \Magento\GoogleTagManager\Helper\Data $helper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Session\SessionManagerInterface $session
    ) {
        $this->helper = $helper;
        $this->registry = $registry;
        $this->cookieManager = $cookieManager;
        $this->jsonHelper = $jsonHelper;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->session = $session;
    }

    /**
     * Send cookies after cart action
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return $this;
        }
        
        $productsToAdd = $this->registry->registry('GoogleTagManager_products_addtocart');
        if (!empty($productsToAdd)) {
            $this->session->setData("GoogleTagManager_products_addtocart_session",$productsToAdd);
        }

        $productsToRemove = $this->registry->registry('GoogleTagManager_products_to_remove');
        if (!empty($productsToRemove)) {
            $this->session->setData("GoogleTagManager_products_to_remove_session",$productsToRemove);
        }

        return $this;
    }
}
