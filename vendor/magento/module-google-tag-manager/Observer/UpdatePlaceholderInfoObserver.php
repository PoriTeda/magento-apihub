<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Observer;

use Magento\Framework\Event\ObserverInterface;

class UpdatePlaceholderInfoObserver implements ObserverInterface
{
    /**
     * @var null|\Magento\GoogleTagManager\Block\ListJson
     */
    protected $blockPromotions = null;

    /**
     * @var \Magento\GoogleTagManager\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param \Magento\GoogleTagManager\Block\ListJson $blockPromotions
     */
    public function __construct(
        \Magento\GoogleTagManager\Helper\Data $helper,
        \Magento\GoogleTagManager\Block\ListJson $blockPromotions
    ) {
        $this->helper = $helper;
        $this->blockPromotions = $blockPromotions;
    }

    /**
     * Fires by the render_block event of the Magento_PageCache module only
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return $this;
        }

        $block = $observer->getEvent()->getBlock();

        // Caching Banner Widget from FPC
        if ($block instanceof \Magento\Banner\Block\Widget\Banner) {
            $this->blockPromotions = $this->blockPromotions
                ->appendBannerBlock($block);
        }

        return $this;
    }
}
