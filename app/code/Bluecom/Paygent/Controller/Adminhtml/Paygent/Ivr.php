<?php

namespace Bluecom\Paygent\Controller\Adminhtml\Paygent;

class Ivr extends \Magento\Backend\App\Action
{
    const ERROR_CODE = 'error';
    const FAILED_RESPONSE_CODE = 'failed';
    const CANCELED_CODE = 'canceled';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJson;
    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $clientFactory;
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var \Bluecom\Paygent\Logger\IvrLogger
     */
    protected $paygentIvrLogger;

    /**
     * Ivr constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJson
     * @param \Magento\Framework\HTTP\ZendClientFactory $clientFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Bluecom\Paygent\Logger\IvrLogger $paygentIvrLogger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Controller\Result\JsonFactory $resultJson,
        \Magento\Framework\HTTP\ZendClientFactory $clientFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Bluecom\Paygent\Logger\IvrLogger $paygentIvrLogger
    ) {
        parent::__construct($context);
        $this->scopeConfig = $scopeConfig;
        $this->resultJson = $resultJson;
        $this->clientFactory = $clientFactory;
        $this->orderRepository = $orderRepository;
        $this->paygentIvrLogger = $paygentIvrLogger;
        $this->paygentIvrLogger->setTimezone(
            new \DateTimeZone($timezone->getConfigTimezone())
        );

    }

    /**
     * Is Allow
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }

    /**
     * Connect to IVR system
     * 
     * @return mixed
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('id');
        if ((int)$orderId) {
            $order = $this->orderRepository->get($orderId);
        } else {
            return false;
        }

        $this->paygentIvrLogger->info('Get Ivr transaction for order: '.$order->getIncrementId());

        $resultJson = $this->resultJson->create();
        $returnValue = array();

        if ($order->getStatus() == \Riki\Sales\Model\ResourceModel\Order\OrderStatus::STATUS_ORDER_CANCELED) {
            $order->setIvrTransaction(self::CANCELED_CODE);
            $returnValue['resultCode'] = "1";
            $returnValue['message'] = __('Order has been canceled');
            $this->paygentIvrLogger->info('Get Ivr transaction for order: '.$order->getIncrementId()).' - Order has been canceled';
        } else {
            //$client = new \Zend_Http_Client();
            $client = $this->clientFactory->create();

            //$urlAPI = 'https://nestle.spv.jp/payment/regist';
            //$urlAPI = 'https://dhkdemo.spv.jp/nestle/payment/regist';
            $urlAPI = $this->scopeConfig->getValue('paygent_config/ivr/registration_request');

            $client->setUri($urlAPI);
            $data = [
                'tradingId' => $order->getIncrementId(),
                'paymentAmount' => (int)$order->getGrandTotal(),
                'paymentClass' => 10,
                'splitCount' => '',
                'siteId' => ''
            ];

            foreach ($data as $k => $v) {
                $client->setParameterPost($k, $v);
            }
            //send http request to paygent
            $response = $client->request('POST');

            if ($response->isError() && !$response->getBody()) {
                $returnValue['resultCode'] = "1";
                $returnValue['message'] = __('Network error. Could not reach gateway server.');
            } else {
                $returnValue = $this->_parseResponseBody($response->getBody());
            }
            if($returnValue['resultCode'] == 0) {
                $order->setIvrTransaction($returnValue['identifier']);
                $message = sprintf('Request to IVR API successful and Transaction Id : %s',$returnValue['identifier']);
                $order->addStatusHistoryComment(__($message), false);
                $this->paygentIvrLogger->info('Get Ivr transaction for order: '.$order->getIncrementId().' - Request to IVR API successful and Transaction Id : '.$returnValue['identifier']);
            } else {
                // set Error
                $order->setIvrTransaction(self::ERROR_CODE);
                $message = sprintf('Request to IVR API failure. Please try again with new order.');
                $order->addStatusHistoryComment(__($message), false);
                $this->paygentIvrLogger->info('Get Ivr transaction for order: '.$order->getIncrementId(). '- Request to IVR API failure. ');
            }
        }

        try {
            $this->orderRepository->save($order);
        } catch (\Exception $e) {
            $this->paygentIvrLogger->error($e->getMessage());
        }

        //write log to file when enable debug flag
        $this->paygentIvrLogger->info('Get Ivr transaction for order: '.$order->getIncrementId(). \Zend_Json::encode($returnValue));

        return $resultJson->setData($returnValue);
    }

    /**
     * Parse Response Body
     * 
     * @param object $body Body
     *
     * @return array
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function _parseResponseBody($body)
    {
        if (strlen($body) == 0) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Could not retrive response body. Please contact us.'));
        }

        $_results = preg_split("/(\r\n)/", $body);

        $returnValue = array();

        foreach ($_results as $data) {
            $xml = simplexml_load_string($data);
            $returnValue['resultCode'] = (string)$xml->resultCode;
            if ($xml->resultCode == 0) {
                $returnValue['identifier'] = (string)$xml->identifier;
            } else {
                $returnValue['message'] =  __('Requested failure ! Could not retrive response code.');
            }
        }

        return $returnValue;
    }

}
