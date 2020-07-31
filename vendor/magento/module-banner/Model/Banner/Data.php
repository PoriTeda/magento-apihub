<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Banner\Model\Banner;

use Magento\Customer\CustomerData\SectionSourceInterface;
use Magento\Banner\Model\Config;

/**
 * Banner section
 */
class Data implements SectionSourceInterface
{
    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Store Banner resource instance
     *
     * @var \Magento\Banner\Model\ResourceModel\Banner
     */
    protected $bannerResource;

    /**
     * Banner instance
     *
     * @var \Magento\Banner\Model\Banner
     */
    protected $banner;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $filterProvider;

    /**
     * @var array
     */
    protected $banners = [];

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Banner\Model\ResourceModel\Banner $bannerResource
     * @param \Magento\Banner\Model\Banner $banner
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\Cms\Model\Template\FilterProvider $filterProvider
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Banner\Model\ResourceModel\Banner $bannerResource,
        \Magento\Banner\Model\Banner $banner,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->bannerResource = $bannerResource;
        $this->banner = $banner;
        $this->storeManager = $storeManager;
        $this->httpContext = $httpContext;
        $this->filterProvider = $filterProvider;
        $this->storeId = $this->storeManager->getStore()->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function getSectionData()
    {
        return [
            'items' => [
                Config::BANNER_WIDGET_DISPLAY_SALESRULE => $this->getSalesRuleRelatedBanners(),
                Config::BANNER_WIDGET_DISPLAY_CATALOGRULE => $this->getCatalogRuleRelatedBanners(),
                Config::BANNER_WIDGET_DISPLAY_FIXED => $this->getFixedBanners(),
            ],
            'store_id' => $this->storeId
        ];
    }

    /**
     * @return array
     */
    protected function getSalesRuleRelatedBanners()
    {
        $appliedRules = [];
        if ($this->checkoutSession->getQuoteId()) {
            $quote = $this->checkoutSession->getQuote();
            if ($quote && $quote->getAppliedRuleIds()) {
                $appliedRules = explode(',', $quote->getAppliedRuleIds());
            }
        }
        return $this->getBannersData($this->bannerResource->getSalesRuleRelatedBannerIds($appliedRules));
    }

    /**
     * @return array
     */
    protected function getCatalogRuleRelatedBanners()
    {
        return $this->getBannersData($this->bannerResource->getCatalogRuleRelatedBannerIds(
            $this->storeManager->getWebsite()->getId(),
            $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_GROUP)
        ));
    }

    /**
     * @return array
     */
    protected function getFixedBanners()
    {
        return $this->getBannersData($this->bannerResource->getActiveBannerIds());
    }

    /**
     * @param array $bannersIds
     * @return array
     */
    protected function getBannersData($bannersIds)
    {
        $banners = [];
        foreach ($bannersIds as $bannerId) {
            if (!isset($this->banners[$bannerId])) {
                $content = $this->bannerResource->getStoreContent($bannerId, $this->storeId);
                if (!empty($content)) {
                    $this->banners[$bannerId] = [
                        'content' => $this->filterProvider->getPageFilter()->filter($content),
                        'types' => $this->banner->load($bannerId)->getTypes(),
                        'id' => $bannerId,
                    ];
                } else {
                    $this->banners[$bannerId] = null;
                }
            }
            $banners[$bannerId] = $this->banners[$bannerId];
        }
        return array_filter($banners);
    }
}
