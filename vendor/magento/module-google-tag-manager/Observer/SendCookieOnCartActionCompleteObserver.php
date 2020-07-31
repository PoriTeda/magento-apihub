<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Observer;

use Magento\Framework\Event\ObserverInterface;

class SendCookieOnCartActionCompleteObserver implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

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
        \Magento\Framework\App\RequestInterface $httpRequest
    ) {
        $this->helper = $helper;
        $this->registry = $registry;
        $this->cookieManager = $cookieManager;
        $this->jsonHelper = $jsonHelper;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->request = $httpRequest;
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
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration(3600)
            ->setPath('/')
            ->setHttpOnly(false);

        if (!empty($productsToAdd) && !$this->request->isXmlHttpRequest()) {
            $this->cookieManager->setPublicCookie(
                \Magento\GoogleTagManager\Helper\Data::GOOGLE_ANALYTICS_COOKIE_NAME,
                rawurlencode(json_encode($productsToAdd)),
                $publicCookieMetadata
            );
        }
        $productsToRemove = $this->registry->registry('GoogleTagManager_products_to_remove');
        if (!empty($productsToRemove && !$this->request->isXmlHttpRequest())) {
            $this->cookieManager->setPublicCookie(
                \Magento\GoogleTagManager\Helper\Data::GOOGLE_ANALYTICS_COOKIE_REMOVE_FROM_CART,
                rawurlencode($this->jsonHelper->jsonEncode($productsToRemove)),
                $publicCookieMetadata
            );
        }
        return $this;
    }
}
