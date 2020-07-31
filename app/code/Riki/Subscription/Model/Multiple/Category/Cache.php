<?php

namespace Riki\Subscription\Model\Multiple\Category;

class Cache
{
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Riki\Subscription\Model\ProfileCacheRepository
     */
    private $subscriptionCacheRepository;

    /**
     * Cache constructor.
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Subscription\Model\ProfileCacheRepository $subscriptionCacheRepository
    ) {
        $this->cache = $cache;
        $this->mathRandom = $mathRandom;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->subscriptionCacheRepository = $subscriptionCacheRepository;
    }

    /**
     * @param $identifier
     * @return bool|mixed
     */
    public function getCache($identifier)
    {
        $cache = $this->cache->load($identifier);
        if ($cache) {
            $cacheDecode = json_decode($cache, true);
            $obj = new \Magento\Framework\DataObject();
            $obj->setData($cacheDecode);
            return $obj;
        }

        return false;
    }

    /**
     * @param $identifier
     */
    public function removeCache($identifier)
    {
        $this->cache->remove($identifier);
    }

    /**
     * @return false|int
     * @throws \Exception
     */
    public function getCacheIdentifier()
    {
        return $this->mathRandom->getUniqueHash($this->getWebsiteId());
    }

    /**
     * Get website id
     *
     * @return int
     */
    public function getWebsiteId()
    {
        return $this->storeManager->getStore()->getWebsiteId();
    }

    /**
     * @param $data
     * @param $identifier
     */
    public function saveCache($data, $identifier)
    {
        if ($data instanceof \Riki\Subscription\Model\Emulator\Order) {
            $paymentMethod = $data->getPayment()->getMethodInstance()->getTitle();
            $data->setData('payment_method', $paymentMethod);
            $dataSave['gw_items_base_price_incl_tax'] = $data->getData('gw_items_base_price_incl_tax');
            $dataSave['shipping_incl_tax'] = $data->getData('shipping_incl_tax');
            $dataSave['gw_price_incl_tax'] = $data->getData('gw_price_incl_tax');
            $dataSave['grand_total'] = $data->getData('grand_total');
            $dataSave['fee'] = $data->getData('fee');
            $dataSave['discount_amount'] = $data->getData('discount_amount');
            $dataSave['applied_rule_ids'] = $data->getData('applied_rule_ids');
            $dataSave['coupon_code'] = $data->getData('coupon_code');
            $dataSave['payment_method'] = $paymentMethod;
        }
        $this->cache->save(json_encode($dataSave), $identifier, [], $this->subscriptionCacheRepository->getCacheLifetime() * 60);
    }
}
