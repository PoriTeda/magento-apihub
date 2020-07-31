<?php
/**
 * Depersonalize customer session data
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model\Layout;

use Magento\Customer\Model\Context;
use Magento\CustomerSegment\Helper\Data;

/**
 * Class DepersonalizePlugin
 */
class DepersonalizePlugin
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var array
     */
    protected $customerSegmentIds;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Magento\PageCache\Model\Config
     */
    protected $cacheConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\PageCache\Model\Config $cacheConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\PageCache\Model\Config $cacheConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->customerSession = $customerSession;
        $this->request = $request;
        $this->moduleManager = $moduleManager;
        $this->httpContext = $httpContext;
        $this->cacheConfig = $cacheConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Before layout generate
     *
     * @param \Magento\Framework\View\LayoutInterface $subject
     * @return void
     */
    public function beforeGenerateXml(\Magento\Framework\View\LayoutInterface $subject)
    {
        if ($this->moduleManager->isEnabled('Magento_PageCache')
            && $this->cacheConfig->isEnabled()
            && !$this->request->isAjax()
            && $subject->isCacheable()
        ) {
            $this->customerSegmentIds = $this->customerSession->getCustomerSegmentIds();
        }
    }

    /**
     * After layout generate
     *
     * @param \Magento\Framework\View\LayoutInterface $subject
     * @param \Magento\Framework\View\LayoutInterface $result
     * @return \Magento\Framework\View\LayoutInterface
     */
    public function afterGenerateXml(\Magento\Framework\View\LayoutInterface $subject, $result)
    {
        if ($this->moduleManager->isEnabled('Magento_PageCache')
            && $this->cacheConfig->isEnabled()
            && !$this->request->isAjax()
            && $subject->isCacheable()
        ) {
            $websiteId = $this->storeManager->getWebsite()->getId();

            $value = isset($this->customerSegmentIds[$websiteId])
                ? $this->customerSegmentIds[$websiteId]
                : [];

            if ($this->httpContext->getValue(Context::CONTEXT_AUTH)) {
                $this->httpContext->setValue(Data::CONTEXT_SEGMENT, $value, []);
            } else {
                $this->httpContext->setValue(Data::CONTEXT_SEGMENT, $value, $value);
            }

            $this->customerSession->setCustomerSegmentIds($this->customerSegmentIds);
        }
        return $result;
    }
}
