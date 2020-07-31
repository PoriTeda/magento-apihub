<?php

namespace Riki\Loyalty\Helper;

use Magento\Framework\App\Helper;
use Riki\Loyalty\Model\ConsumerDb\ShoppingPoint;

class Api extends Helper\AbstractHelper
{
    const XPATH_SOAP_BASE_URL = 'riki_loyalty/api/base_url';
    const XPATH_SOAP_BASE_CLIENT_INFO = 'riki_loyalty/api/client_info';
    const XPATH_SOAP_BASE_CLIENT_INFO_DOMAIN = 'riki_loyalty/api/client_info_domain';

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $_searchBuilder;

    /**
     * @var \Magento\Sales\Model\OrderRepository
     */
    protected $_orderRepository;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    )
    {
        parent::__construct($context);
        $this->_orderRepository = $orderRepository;
        $this->_searchBuilder = $searchCriteriaBuilder;
    }

    /**
     * Build SOAP response from ConsumerDB to array
     *
     * @param object $response
     * @param integer $headerIndex
     * @return array
     */
    public function buildArray($response, $headerIndex)
    {
        $pointHistory = array_map(function($value) {
            return $value->array;
        }, $response->return);
        $result = [];
        $pointIndex = array_map('strtolower', $pointHistory[$headerIndex]);
        foreach ($pointHistory as $key => $value) {
            if ($key < ($headerIndex + 1)) {
                continue;
            }
            $result[] = array_combine($pointIndex, $value);
        }
        return $result;
    }

    /**
     * Add order id into array of point history
     *
     * @param array $pointHistory
     * @return array
     */
    public function addOrderId($pointHistory)
    {
        $arrOrderNo = array_map(function ($value) {
            return $value['order_no'];
        }, $pointHistory);
        $incrementIds = array_filter($arrOrderNo);
        $result = [];
        if (sizeof($incrementIds)) {
            $filter = $this->_searchBuilder->addFilter('increment_id', array_unique($incrementIds), 'in');
            /** @var \Magento\Sales\Model\ResourceModel\Order\Collection $orders */
            $orders = $this->_orderRepository->getList($filter->create());
            /** @var \Magento\Sales\Model\Order $order*/
            if ($orders->getSize()) {
                foreach ($orders as $order) {
                    $result[$order->getIncrementId()] = $order->getId();
                }
            }
        }
        foreach ($pointHistory as $key => $item) {
            $pointHistory[$key]['order_id'] = isset($result[$item['order_no']]) ? $result[$item['order_no']] : null;
        }
        return $pointHistory;
    }

    /**
     * Get point status label
     *
     * @param integer $status
     * @return string
     */
    public function getPointStatusLabel($status)
    {
        switch ($status) {
            case ShoppingPoint::ISSUE_STATUS_TEMP:
                return __('Addition (temporary issue)');
            case ShoppingPoint::ISSUE_STATUS_ADDING:
                return __('Addition (valid)');
            case ShoppingPoint::ISSUE_STATUS_INVALID:
                return __('Invalid');
            case ShoppingPoint::ISSUE_STATUS_SUBTRACTION:
                return __('Subtraction');
            default:
                return $status;
        }
    }

    /**
     * Get point issue type label
     *
     * @param integer $type
     * @return string
     */
    public function getIssueTypeLabel($type)
    {
        switch ($type) {
            case ShoppingPoint::ISSUE_TYPE_PURCHASE:
                return __('On purchase');
            case ShoppingPoint::ISSUE_TYPE_REVIEW:
                return __('Product review');
            case ShoppingPoint::ISSUE_TYPE_QUESTION:
                return __('Questionnaire');
            case ShoppingPoint::ISSUE_TYPE_ADJUSTMENT:
                return __('Adjustment');
            case ShoppingPoint::ISSUE_TYPE_REGISTER:
                return __('Member registration');
            case ShoppingPoint::ISSUE_TYPE_FREE_GIFT:
                return __('Free gift exchange');
            case ShoppingPoint::ISSUE_TYPE_DISCOUNT:
                return __('Discount available');
            case ShoppingPoint::ISSUE_TYPE_SITE_VISIT:
                return __('Site visit');
            case ShoppingPoint::ISSUE_TYPE_GAME:
                return __('Game');
            case ShoppingPoint::ISSUE_TYPE_CAMPAIGN:
                return __('Campaign');
            case ShoppingPoint::ISSUE_TYPE_CONTENT_USAGE:
                return __('Content usage');
            case ShoppingPoint::ISSUE_TYPE_POINT_EXCHANGE:
                return __('Point exchange');
            case ShoppingPoint::ISSUE_TYPE_OTHER:
                return __('Other');
            default:
                return $type;
        }
    }

    /**
     * Get point type label
     *
     * @param integer $type
     * @return string
     */
    public function getTypeLabel($type)
    {
        switch ($type) {
            case ShoppingPoint::TYPE_POINT:
                return __('Shopping point');
            case ShoppingPoint::TYPE_COIN:
                return __('Digital point');
            default:
                return $type;
        }
    }

    /**
     * Get config url for SOAP API
     *
     * @param string $wsdl
     * @return string
     */
    public function soapBaseUrl($wsdl)
    {
        $config = $this->scopeConfig->getValue(self::XPATH_SOAP_BASE_URL);
        return rtrim($config, '/') . $wsdl;
    }

    /**
     * Build response of sub profile from consumer db
     *
     * @param array $response
     * @param integer $headerIndex
     * @return array
     */
    public function customerSubValue($response, $headerIndex)
    {
        $arrResult = $this->buildArray($response, $headerIndex);
        if (!sizeof($arrResult)) {
            return $arrResult;
        }
        $subResult = [];
        foreach ($arrResult as $item) {
            $subResult[$item['customer_code']][$item['subprofile_id']] = [
                'subprofile_name' => $item['subprofile_name'],
                'value_name' => $item['value_name']
            ];
        }
        return $subResult;
    }

    /**
     * Parser response code and message from KSS API
     *
     * @param object $response
     * @return array
     */
    public function responseCode($response)
    {
        if (is_string($response->return[0])) {
            $responseCode = $response->return[0];
        } elseif (is_array($response->return[0]->array)) {
            $responseCode = $response->return[0]->array[0];
        } else {
            $responseCode = $response->return[0]->array;
        }
        if (is_string($response->return[1])) {
            $msg = $response->return[1];
        } elseif (is_array($response->return[1]->array)) {
            $msg = $response->return[1]->array[0];
        } else {
            $msg = $response->return[1]->array;
        }
        return ['code' => $responseCode, 'msg' => $msg];

    }

    /**
     * Get config api client info
     *
     * @return mixed
     */
    public function getApiClientInfo()
    {
        return $this->scopeConfig->getValue(self::XPATH_SOAP_BASE_CLIENT_INFO);
    }
    /**
     * Get config api client info domain
     *
     * @return mixed
     */
    public function getApiClientInfoDomain(){
        return $this->scopeConfig->getValue(self::XPATH_SOAP_BASE_CLIENT_INFO_DOMAIN);
    }
}