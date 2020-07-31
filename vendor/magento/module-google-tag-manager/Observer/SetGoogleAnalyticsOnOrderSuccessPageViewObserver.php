<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GoogleTagManager\Observer;

use Magento\Framework\Event\ObserverInterface;

class SetGoogleAnalyticsOnOrderSuccessPageViewObserver implements ObserverInterface
{
    /**
     * @var \Magento\GoogleTagManager\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\App\ViewInterface
     */
    protected $view;

    /**
     * @param \Magento\GoogleTagManager\Helper\Data $helper
     * @param \Magento\Framework\App\ViewInterface $view
     */
    public function __construct(
        \Magento\GoogleTagManager\Helper\Data $helper,
        \Magento\Framework\App\ViewInterface $view
    ) {
        $this->helper = $helper;
        $this->view = $view;
    }

    /**
     * Add order information into GA block to render on checkout success pages
     * The method overwrites the GoogleAnalytics observer method by the system.xml event settings
     *
     * Fired by the checkout_onepage_controller_success_action and
     * checkout_multishipping_controller_success_action events
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isGoogleAnalyticsAvailable()) {
            return $this;
        }

        $orderIds = $observer->getEvent()->getOrderIds();
        if (empty($orderIds) || !is_array($orderIds)) {
            return $this;
        }
        /** @var \Magento\GoogleTagManager\Block\Ga $block */
        $block = $this->view->getLayout()->getBlock('google_analyticsuniversal');
        if ($block) {
            $block->setOrderIds($orderIds);
        }

        return $this;
    }
}
