<?php

namespace Riki\Customer\Block\Account;

use Magento\Framework\View\Element\Template;

class ValidateSession extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'js/validate-session.phtml';

    /**
     * @var \Magento\PageCache\Model\Config
     */
    protected $pageCacheConfig;

    /**
     * ValidateSession constructor.
     *
     * @param Template\Context $context
     * @param \Magento\PageCache\Model\Config $pageCacheConfig
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\PageCache\Model\Config $pageCacheConfig,
        array $data = []
    )
    {
        parent::__construct($context, $data);

        $this->pageCacheConfig = $pageCacheConfig;
    }

    /**
     * @return string
     */
    public function getSessionValidationUrl()
    {
        return $this->getUrl('customer/account/validatesession');
    }

    /**
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->_urlBuilder->getCurrentUrl();
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _toHtml()
    {
        if ($this->pageCacheConfig->getType() == \Magento\PageCache\Model\Config::VARNISH
            && $this->pageCacheConfig->isEnabled()
            && $this->_scopeConfig->getValue('sso_login_setting/sso_group/use_sso_login', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)
            && $this->getLayout()->isCacheable()
        ) {
            return parent::_toHtml();
        }

        return '';
    }
}
