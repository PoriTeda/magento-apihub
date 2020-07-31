<?php

namespace Riki\Customer\Model;

class Cookie
{
    const COOKIE_NO_CACHE = 'X-Magento-Nocache';
    const CUSTOMER_ACCESS_TOKEN = 'customer_access_token';
    const DOMAIN_COOKIE = "nestle.jp";

    /**
     * @var \Magento\Framework\Stdlib\CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory
     */
    protected $cookieMetadataFactory;

    /**
     * @var \Magento\PageCache\Model\Config
     */
    protected $pageCacheConfig;

    /**
     * Cookie constructor.
     *
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\PageCache\Model\Config $pageCacheConfig
     */
    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\PageCache\Model\Config $pageCacheConfig
    )
    {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->pageCacheConfig = $pageCacheConfig;
    }

    /**
     * Updating cookies to refresh private cache.
     * X-Magento-Vary will be set/delete by default.
     *
     * @param bool $reloadCustomerData
     *
     * @return $this
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function sendInvalidatePrivateCache($reloadCustomerData = false)
    {
        // Delete customer data by deleting mage-cache-sessid cookie.
        $cookieMetadata = $this->cookieMetadataFactory
            ->createSensitiveCookieMetadata()
            ->setPath('/');
        $this->cookieManager->deleteCookie('mage-cache-sessid', $cookieMetadata);

        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration(\Magento\Framework\App\PageCache\Version::COOKIE_PERIOD)
            ->setPath('/')
            ->setHttpOnly(false);
        if ($reloadCustomerData) {
            // Load customer data by setting private_content_version cookie.
            $this->cookieManager->setPublicCookie(\Magento\Framework\App\PageCache\Version::COOKIE_NAME, md5(rand() . time()), $publicCookieMetadata);
        } else {
            $this->cookieManager->deleteCookie(\Magento\Framework\App\PageCache\Version::COOKIE_NAME, $publicCookieMetadata);
        }

        return $this;
    }

    /**
     * Varnish will pass the request if it see this cookie.
     *
     * @return $this
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function sendNoCache()
    {
        if (!$this->pageCacheConfig->isEnabled()
            || $this->pageCacheConfig->getType() != \Magento\PageCache\Model\Config::VARNISH
        ) {
            return $this;
        }

        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDurationOneYear()
            ->setPath('/')
            ->setHttpOnly(false);
        $this->cookieManager->setPublicCookie(self::COOKIE_NO_CACHE, true, $publicCookieMetadata);
    }

    /**
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function deleteCookieToken() {
        $publicCookieMetadataToken = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setPath('/')
            ->setDomain(self::DOMAIN_COOKIE)
            ->setHttpOnly(false);
        $this->cookieManager->deleteCookie(self::CUSTOMER_ACCESS_TOKEN, $publicCookieMetadataToken);
    }
}