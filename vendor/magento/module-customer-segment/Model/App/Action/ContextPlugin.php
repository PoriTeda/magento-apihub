<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerSegment\Model\App\Action;

/**
 * Class ContextPlugin
 */
class ContextPlugin
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * @var \Magento\CustomerSegment\Model\Customer
     */
    protected $customerSegment;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param \Magento\CustomerSegment\Model\Customer $customerSegment
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\CustomerSegment\Model\Customer $customerSegment,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
        $this->customerSegment = $customerSegment;
        $this->storeManager = $storeManager;
    }

    /**
     * @param \Magento\Framework\App\ActionInterface $subject
     * @param callable $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @return \Closure
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundDispatch(
        \Magento\Framework\App\ActionInterface $subject,
        \Closure $proceed,
        \Magento\Framework\App\RequestInterface $request
    ) {
        if ($this->customerSession->getCustomerId()) {
            $customerSegmentIds = $this->customerSegment->getCustomerSegmentIdsForWebsite(
                $this->customerSession->getCustomerId(),
                $this->storeManager->getWebsite()->getId()
            );
            $this->httpContext->setValue(
                \Magento\CustomerSegment\Helper\Data::CONTEXT_SEGMENT,
                $customerSegmentIds,
                []
            );
        } else {
            $this->httpContext->setValue(
                \Magento\CustomerSegment\Helper\Data::CONTEXT_SEGMENT,
                [],
                []
            );
        }
        return $proceed($request);
    }
}
