<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Model\Plugin;

use Magento\Framework\View\LayoutInterface;

class Layout
{
    /**
     * @var \Magento\GoogleTagManager\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     */
    public function __construct(
        \Magento\GoogleTagManager\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param LayoutInterface $subject
     * @param  \Magento\Framework\View\Element\BlockInterface $result
     * @return \Magento\Framework\View\Element\BlockInterface
     */
    public function afterCreateBlock(LayoutInterface $subject, $result)
    {
        if (!$this->helper->isTagManagerAvailable()) {
            return $result;
        }

        if ($result instanceof \Magento\Banner\Block\Widget\Banner) {
            /** @var \Magento\GoogleTagManager\Block\ListJson $jsonBlock */
            $jsonBlock = $subject->getBlock('banner_impression');
            if (is_object($jsonBlock)) {
                $jsonBlock->appendBannerBlock($result);
            }
        }
        return $result;
    }
}
