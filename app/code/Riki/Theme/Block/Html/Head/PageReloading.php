<?php

namespace Riki\Theme\Block\Html\Head;

use Magento\Framework\App\Http\Context;
use Magento\Framework\View\Element\Template;

class PageReloading extends Template
{
    /**
     * @var \Riki\Session\Helper\PageReloading
     */
    protected $_pageReloadingHelper;

    /**
     * @var Context
     */
    protected $httpContext;

    /**
     * PageReloading constructor.
     *
     * @param \Riki\Session\Helper\PageReloading $pageReloadingHelper
     * @param Template\Context $context
     * @param Context $httpContext
     * @param array $data
     */
    public function __construct(
        \Riki\Session\Helper\PageReloading $pageReloadingHelper,
        Template\Context $context,
        Context $httpContext,
        array $data = []
    )
    {
        $this->_pageReloadingHelper = $pageReloadingHelper;
        $this->httpContext = $httpContext;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function toHtml()
    {
        if (!(bool)$this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH)) {
            return '';
        }

        return parent::toHtml();
    }

    /**
     * Get reloading interval
     *
     * @return mixed
     */
    public function getInterval()
    {
        $threshold = $this->_scopeConfig->getValue('web/cookie/cookie_lifetime');
        if (!$threshold) {
            $threshold = $this->_pageReloadingHelper->getDefaultInterval();
        }
        return $threshold + $this->_pageReloadingHelper->getExtraInterval();
    }

    /**
     * Get page reloading url
     *
     * @return string
     */
    public function getReloadingUrl()
    {
        return $this->_urlBuilder->getUrl('customer/account/logout');
    }
}
