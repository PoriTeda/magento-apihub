<?php
namespace Riki\Catalog\Cron;

class FuturePrice
{
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /** @var \Magento\Catalog\Model\Product\Action  */
    protected $productAction;

    /** @var \Psr\Log\LoggerInterface  */
    protected $logger;

    /**
     * FuturePrice constructor.
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Catalog\Model\Product\Action $productAction
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Model\Product\Action $productAction,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->functionCache = $functionCache;
        $this->storeManager = $storeManager;
        $this->dateTime = $dateTime;
        $this->localeDate = $localeDate;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productRepository = $productRepository;
        $this->productAction = $productAction;
        $this->logger = $logger;
    }

    /**
     * Update future price
     *
     * @return void
     */
    public function execute()
    {
        foreach ($this->storeManager->getStores(true) as $store) {
            // future_price & future_gps_price is store scope, need emulate to fetch correct eav attribute
            $this->updateFuturePrice($store);
            $this->updateFuturePriceGps($store);
        }
    }

    /**
     * Update future price
     *
     * @param \Magento\Store\Model\Store $store
     *
     * @return void
     */
    public function updateFuturePrice(\Magento\Store\Model\Store $store)
    {
        $ids = $this->getFuturePriceProductIds();
        $query = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $ids, 'in')
            ->create();
        $items = $this->productRepository->getList($query)->getItems();
        /** @var \Magento\Catalog\Model\Product $item */
        foreach ($items as $item) {

            $updateData = [];

            $futurePrice = $item->getData('future_price');
            if (!is_null($futurePrice)) {

                $updateData['price'] = $futurePrice;
                $updateData['future_price'] = null;
            }

            if ($item->getData('future_price_from')) {
                $updateData['future_price_from'] = null;
            }

            if (count($updateData)) {
                try {
                    $this->productAction->updateAttributes([$item->getId()], $updateData, $store->getId());
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }
    }

    /**
     * Update future price gps
     *
     * @param \Magento\Store\Model\Store $store
     *
     * @return  void
     */
    public function updateFuturePriceGps(\Magento\Store\Model\Store $store)
    {
        $ids = $this->getFuturePriceGpsProductIds();
        $query = $this->searchCriteriaBuilder
            ->addFilter('entity_id', $ids, 'in')
            ->create();
        $items = $this->productRepository->getList($query)->getItems();
        /** @var \Magento\Catalog\Model\Product $item */
        foreach ($items as $item) {

            $updateData = [];

            $futurePriceGps = $item->getData('future_gps_price');
            if (!is_null($futurePriceGps)) {
                $updateData['gps_price'] = $futurePriceGps;
                $updateData['future_gps_price'] = null;
            }

            $futurePriceGpsEc = $item->getData('future_gps_price_ec');
            if (!is_null($futurePriceGpsEc)) {
                $updateData['gps_price_ec'] = $futurePriceGpsEc;
                $updateData['future_gps_price_ec'] = null;
            }

            if ($item->getData('future_gps_price_from')) {
                $updateData['future_gps_price_from'] = null;
            }

            if (count($updateData)) {
                try {
                    $this->productAction->updateAttributes([$item->getId()], $updateData, $store->getId());
                } catch (\Exception $e) {
                    $this->logger->critical($e);
                }
            }
        }
    }

    /**
     * Get product ids which match future_price_gps_from
     *
     * @return array
     */
    public function getFuturePriceGpsProductIds()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }
        // future_gps_price_from is scope global
        $timestamp = $this->localeDate->scopeTimeStamp(null);
        $currDate = $this->dateTime->formatDate($timestamp, false);
        $query = $this->searchCriteriaBuilder
            ->addFilter('future_gps_price_from', $currDate)
            ->create();
        $items = $this->productRepository->getList($query)->getItems();
        $result = [];
        foreach ($items as $item) {
            $result[] = $item->getId();
        }
        $result = array_unique($result);
        $this->functionCache->store($result);

        return $result;
    }

    /**
     * Get product ids which match future_price_from
     *
     * @return array
     */
    public function getFuturePriceProductIds()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }
        // future_gps_price_from is scope global
        $timestamp = $this->localeDate->scopeTimeStamp(null);
        $currDate = $this->dateTime->formatDate($timestamp, false);
        $query = $this->searchCriteriaBuilder
            ->addFilter('future_price_from', $currDate)
            ->create();
        $items = $this->productRepository->getList($query)->getItems();
        $result = [];
        foreach ($items as $item) {
            $result[] = $item->getId();
        }
        $result = array_unique($result);
        $this->functionCache->store($result);

        return $result;
    }
}
