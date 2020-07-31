<?php

namespace Riki\MachineApi\Helper;

use \Magento\Framework\App\Helper\Context;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_MACHINE_DEFAULT_PLACE = 'freemachine\stock\default_place';

    /*requirement: default warehouse for order which is placed by machine api, is bizex*/
    const MACHINE_DEFAULT_PLACE = 2;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrl;

    public function __construct(
        \Magento\Backend\Model\UrlInterface $backendUrl,
        Context $context
    ) {
        parent::__construct($context);
        $this->backendUrl = $backendUrl;
    }

    /**
     * Get default warehouse for order which is placed by machine api
     *
     * @return int|mixed
     */
    public function getMachineDefaultPlace()
    {
        $defaultPlace =  $this->scopeConfig->getValue(
            self::CONFIG_MACHINE_DEFAULT_PLACE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$defaultPlace) {
            $defaultPlace = self::MACHINE_DEFAULT_PLACE;
        }

        return $defaultPlace;
    }

    /**
     * Check whether current request is a machine API request
     *
     * @return bool
     */
    public function isMachineApiRequest()
    {
        $pathInfo = $this->_request->getRequestUri();
        $pattern = '#V1/mm#';
        if (preg_match($pattern, $pathInfo, $match)) {
            return true;
        }
        return false;
    }

    public function getMachineUrl()
    {
        return $this->backendUrl->getUrl('machine/b2c/machines', ['_current' => true]);
    }
}
