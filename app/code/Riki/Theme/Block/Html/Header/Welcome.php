<?php

namespace Riki\Theme\Block\Html\Header;

class Welcome extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    //protected $_template = 'html/header/welcome.phtml';

    /**
     * @var \Magento\Framework\App\Http\Context
     */
    protected $httpContext;

    /**
     * Welcome constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\App\Http\Context $httpContext
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\App\Http\Context $httpContext,
        array $data = []
    )
    {
        $this->httpContext = $httpContext;
        parent::__construct($context, $data);
    }

    /**
     * @return boolean
     */
    public function isLoggedIn()
    {
        return $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    /**
     * @return string|null
     */
    public function getDefaultWelcome()
    {
        $configValue = $this->_scopeConfig->getValue(
            'design/header/welcome',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $configValue ? $configValue : __('Welcome <strong>Guest</strong>');
    }
}
