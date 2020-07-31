<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Block\Plugin\Banner\Widget;

class Banner
{
    /**
     * @var \Magento\GoogleTagManager\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\GoogleTagManager\Model\Banner\Collector
     */
    protected $bannerCollector;

    /**
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param \Magento\GoogleTagManager\Model\Banner\Collector $bannerCollector
     */
    public function __construct(
        \Magento\GoogleTagManager\Helper\Data $helper,
        \Magento\GoogleTagManager\Model\Banner\Collector $bannerCollector
    ) {
        $this->helper = $helper;
        $this->bannerCollector = $bannerCollector;
    }

    /**
     * @param \Magento\Banner\Block\Widget\Banner $subject
     * @return void
     */
    public function beforeToHtml(\Magento\Banner\Block\Widget\Banner $subject)
    {
        if ($this->helper->isTagManagerAvailable()) {
            $this->bannerCollector->addBannerBlock($subject);
        }
    }
}
