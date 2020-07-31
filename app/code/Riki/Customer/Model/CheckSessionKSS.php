<?php

namespace Riki\Customer\Model;

use \Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class CheckSessionKSS
{
    /**
     * @var \Riki\Customer\Helper\ConsumerLog
     */
    protected $apiLogger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * @var \Riki\Customer\Helper\Api
     */
    protected $apiCustomerHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $coreLogger;

    /**
     * @var TimezoneInterface
     */
    protected $dateTime;

    /**
     * @var array
     */
    protected $apiResponse;

    /**
     * @var \Riki\Customer\Helper\ConsumerDb\Soap
     */
    protected $soapHelper;

    /**
     * @var \Riki\Framework\Webapi\Soap\ClientFactory
     */
    protected $soapClientFactory;

    /**
     * CheckSessionKSS constructor.
     * @param TimezoneInterface $dateTime
     * @param \Riki\Customer\Helper\ConsumerLog $apiLogger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\Customer\Helper\Api $apiCustomerHelper
     * @param \Psr\Log\LoggerInterface $coreLogger
     * @param \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper
     * @param \Riki\Framework\Webapi\Soap\ClientFactory $soapClientFactory
     */
    public function __construct(
        TimezoneInterface $dateTime,
        \Riki\Customer\Helper\ConsumerLog $apiLogger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Customer\Helper\Api $apiCustomerHelper,
        \Psr\Log\LoggerInterface $coreLogger,
        \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper,
        \Riki\Framework\Webapi\Soap\ClientFactory $soapClientFactory
    ) {
        $this->dateTime = $dateTime;
        $this->apiLogger = $apiLogger;
        $this->scopeConfig = $scopeConfig;
        $this->apiCustomerHelper = $apiCustomerHelper;
        $this->coreLogger = $coreLogger;
        $this->soapHelper = $soapHelper;
        $this->soapClientFactory = $soapClientFactory;
    }

    /**
     * @param $ssoCookie
     *
     * @return array
     */
    public function checkSession($ssoCookie)
    {
        if (is_null($this->apiResponse)) {
            $this->apiResponse = [];

            // Call API checkSession
            $dateTime = $this->dateTime->date()->format('Y/m/d H:i:s');

            $soapConfig = $this->soapHelper->getCommonRequestParams();

            $wsdl = $this->apiCustomerHelper->getConsumerApiUrl('/CheckSessionService?wsdl');
            $endPoint = $this->apiCustomerHelper->getConsumerApiUrl('/CheckSessionService.CheckSessionServiceHttpSoap12Endpoint/');
            $param3 = $this->scopeConfig->getValue('consumer_db_api_url/customer/param3', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
            $param4 = $this->scopeConfig->getValue('consumer_db_api_url/customer/param4', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

            $soapClient = $this->soapClientFactory->create($wsdl, $soapConfig);
            $soapClient->setLocation($endPoint);
            $params = [];
            $params[] = new \SoapVar($param3, XSD_STRING, null, null, 'clientInfo');
            $params[] = new \SoapVar($param4, XSD_STRING, null, null, 'clientInfo');
            $params[] = new \SoapVar($dateTime, XSD_STRING, null, null, 'clientInfo');
            $params[] = new \SoapVar($ssoCookie, XSD_STRING, null, null, 'sessionId');

            try {
                $response = $soapClient->checkSession(new \SoapVar($params, SOAP_ENC_OBJECT));

                if (property_exists($response, 'return')
                    && isset($response->return[0])
                    && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $response->return[0]
                ) {
                    $messageCode = $response->return[0];
                    $message = $response->return[1];
                    $customerId = $response->return[3];
                    $sessionID = $response->return[4];
                    $this->apiResponse = [
                        'messageCode' => $messageCode,
                        'message' => $message,
                        'consumerDbId' => $customerId,
                        'SSOSID' => $sessionID,
                    ];

                    $request = $soapClient->getLastRequest();
                    $this->apiLogger->saveAPILog('checkSession', 'SSOSID is valid', 1, $request, $this->apiResponse);
                } else {
                    $this->apiLogger->saveAPILog('checkSession', 'SSOSID is invalid', 0, $soapClient->getLastRequest(), $response);
                }
            } catch (\Exception $e) {
                $this->coreLogger->critical($e);

                $this->apiLogger->saveAPILog('checkSession', 'SSOSID is invalid', 0, $soapClient->getLastRequest(), $e->getMessage());
            }
        }

        return $this->apiResponse;
    }
}
