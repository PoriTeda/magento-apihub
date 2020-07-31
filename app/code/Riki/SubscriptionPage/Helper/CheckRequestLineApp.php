<?php

namespace Riki\SubscriptionPage\Helper;

use Magento\Framework\App\Helper\Context;

class CheckRequestLineApp extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected  $_request;

    /**
     * CheckRequestLineApp constructor.
     *
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);
        $this->_request = $context->getRequest();
    }


    /**
     * Check request add params
     *
     * @param $url
     * @return string
     */
    public function checkRequestAddParam($url){
        if (!empty($url)) {
            $requestUri = $this->_request->getRequestUri();
            $pattern  ='#lineapp=true#';
            if ($requestUri !=''){
                if( !preg_match($pattern,$url) && preg_match($pattern,$requestUri)){
                    return $url .'?lineapp=true';
                }
            }
        }

        return $url;
    }

    /**
     * Get link form action
     *
     * @param $pathUrl
     * @return string
     */
    public function getLinkFormAction($pathUrl)
    {
        $params = $this->_request->getParams();
        if (isset($params['lineapp'])) {
            return $pathUrl . '?lineapp=true';
        }
        return $pathUrl;
    }



}