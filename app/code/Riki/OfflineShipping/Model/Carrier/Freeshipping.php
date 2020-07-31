<?php

namespace Riki\OfflineShipping\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Backend\App\Area\FrontNameResolver;

class Freeshipping extends \Magento\OfflineShipping\Model\Carrier\Freeshipping
{
    const XML_CONFIG_METHOD_SHIPPING_FREE_METHOD = 'carriers/freeshipping/is_showfrontend';

    /**
     * @var \Magento\Framework\App\State
     */
    protected $_appState;

    /**
     * Freeshipping constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Framework\App\State $appState
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Magento\Framework\App\State $appState,
        array $data = []
    )
    {
        $this->_appState = $appState;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $rateResultFactory, $rateMethodFactory, $data);
    }

    /**
     * @param RateRequest $request
     * @return bool|\Magento\Shipping\Model\Rate\Result
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }
        $isAdmin = $this->_appState->getAreaCode() == FrontNameResolver::AREA_CODE;
        // it is config in configuration bo

        $isShowFrontend = $this->getConfigData('is_showfrontend');

        if (!$isAdmin && !$isShowFrontend) {
            return false;
        }
        return parent::collectRates($request);
    }

}
