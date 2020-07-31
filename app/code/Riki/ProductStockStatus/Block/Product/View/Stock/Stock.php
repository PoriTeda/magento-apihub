<?php

namespace Riki\ProductStockStatus\Block\Product\View\Stock;

use Magento\Customer\Model\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\View\Element\Template;

/**
 * Recurring payment view stock
 */
class Stock extends \Magento\ProductAlert\Block\Product\View
{
    /**
     * @var \Magento\Customer\Model\Url
     */
    protected $userUrl;

    /**
     * @var HttpContext
     */
    protected $_httpContext;

    /* @var \Magento\Customer\Model\Session */
    protected $_customerSession;

    protected $ssoUrl;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * Login constructor.
     *
     * @param Template\Context $context
     * @param \Magento\Customer\Model\Url $url
     * @param HttpContext $httpContext
     * @param \Magento\ProductAlert\Helper\Data $helper
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\Helper\PostHelper $coreHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\Customer\Helper\SsoUrl $ssoUrl
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        \Magento\Customer\Model\Url $url,
        HttpContext $httpContext,
        \Magento\ProductAlert\Helper\Data $helper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\Helper\PostHelper $coreHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Customer\Helper\SsoUrl $ssoUrl,
        array $data = []
    )
    {
        parent::__construct($context, $helper, $registry, $coreHelper, $data);
        $this->userUrl = $url;
        $this->_httpContext = $httpContext;
        $this->_customerSession = $customerSession;
        $this->ssoUrl = $ssoUrl;
        $this->_request = $context->getRequest();
    }

    /**
     * @return bool
     */
    public function isLogin()
    {
        if ($this->_httpContext->getValue(Context::CONTEXT_AUTH)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return KSS login Link
     *
     * @return mixed|string
     */
    public function getLoginKssUrl()
    {
        return $this->ssoUrl->getLoginUrl($this->_urlBuilder->getCurrentUrl().'?stock=popup');
    }

    /**
     * return KSS register member link
     *
     * @return mixed
     */
    public function getRegisterKssUrl()
    {
        return $this->ssoUrl->getRegisterUrl($this->_urlBuilder->getCurrentUrl());
    }

    public function getLogoutKssUrl()
    {
        return $this->ssoUrl->getLogoutUrl();
    }

    /**
     * @return \Magento\Customer\Model\Url
     */
    public function getUserUrl()
    {
        return $this->userUrl;
    }

    /**
     * @return mixed
     */
    public function getMediaUrl()
    {
        return $this->_storeManager->getStore()->getBaseUrl
        (
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );
    }

    /**
     * Prepare stock info
     *
     * @param string $template
     *
     * @return \Magento\Framework\View\Element\Template
     */
    public function setTemplate($template)
    {
        $this->setSignupUrl($this->_helper->getSaveUrl('stock'));
        //check show alert product
        if ($this->_helper->isStockAlertAllowed()){
            if ($this->getProduct()->isSaleable()) {
                $template = null;
            }
        }else {
            //hidden template when disable show alert stock
            $template = null;
        }

        return parent::setTemplate($template);
    }

    public function getCustomerEmail()
    {
        if ($this->_customerSession->isLoggedIn()) {
            return $this->_customerSession->getCustomer()->getEmail();
        }
        return '';
    }

    /**
     * Check login redirect
     *
     * @return mixed|string
     */
    public function checkPopup(){
        $popup = $this->_request->getParam('stock');
        if($popup){
            return $popup;
        }else{
            return '';
        }
    }
}
