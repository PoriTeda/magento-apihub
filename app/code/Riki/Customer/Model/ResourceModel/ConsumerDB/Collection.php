<?php
namespace Riki\Customer\Model\ResourceModel\ConsumerDB;

use Magento\Framework\Api\Search\SearchResultInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Psr\Log\LoggerInterface;

class Collection extends \Magento\Framework\Data\Collection implements \Magento\Framework\Api\Search\SearchResultInterface
{
    /**
     * @var CONST_DEFAULT_COUNTRY
     */
    const CONST_DEFAULT_COUNTRY = 'JP';

    /**
     * @var
     */
    protected $aggregations;

    /**
     * @var
     */
    protected $searchCriteria;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var int
     */
    protected $totalCount;

    /**
     * @var \Riki\Customer\Helper\ConsumerLog
     */
    protected $_apiLogger;

    /**
     * @var \Riki\Customer\Helper\Api
     */
    protected $_apiConsumerHelper;
    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Riki\Customer\Helper\ConsumerDb\Soap
     */
    protected $soapHelper;

    /**
     * @var \Riki\Framework\Webapi\Soap\ClientFactory
     */
    protected $soapClientFactory;

    /**
     * @var array
     */
    protected $_mappingAttribute = array(
        'KEY_NAME' => 'name_kanji',
        'KEY_NAME_KANA' => 'name_katakana',
        'KEY_CUSTOMER_CODE' => 'customer_id',
        'KEY_EMAIL' => 'email',
        'KEY_POSTAL_CODE' => 'postcode',
        'KEY_ADDRESS' => 'address',
        'KEY_PHONE_NUMBER' => 'phone_number',
        'KEY_COM_PH_NUM' => 'company_phone_number',
        'KEY_COM_NAME' => 'company_name',
        'KEY_CUSTOMER_GROUP' => 'membership_type'
    );

    /**
     * Collection constructor.
     * @param LoggerInterface $logger
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Riki\Customer\Model\ConsumerLog $consumerLog
     * @param \Riki\Customer\Helper\ConsumerLog $apiLogger
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\Customer\Helper\Api $apiConsumerHelper
     * @param \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper
     * @param \Riki\Framework\Webapi\Soap\ClientFactory $soapClientFactory
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\Customer\Model\ConsumerLog $consumerLog,
        \Riki\Customer\Helper\ConsumerLog $apiLogger,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Customer\Helper\Api $apiConsumerHelper,
        \Riki\Customer\Helper\ConsumerDb\Soap $soapHelper,
        \Riki\Framework\Webapi\Soap\ClientFactory $soapClientFactory
    ) {
        $this->_logger = $logger;
        $this->_dateTime = $dateTime;
        $this->_consumerLog = $consumerLog;
        $this->_apiLogger = $apiLogger;
        $this->_scopeConfig = $scopeConfig;
        $this->_apiConsumerHelper = $apiConsumerHelper;
        $this->soapHelper = $soapHelper;
        $this->soapClientFactory = $soapClientFactory;
    }

    /**
     * @return \Magento\Framework\Api\Search\AggregationInterface
     */
    public function getAggregations()
    {
        return $this->aggregations;
    }

    /**
     * @param \Magento\Framework\Api\Search\AggregationInterface $aggregations
     * @return void
     */
    public function setAggregations($aggregations)
    {
        $this->aggregations = $aggregations;
    }

    /**
     * @return \Magento\Framework\Api\Search\SearchCriteriaInterface|null
     */
    public function getSearchCriteria()
    {
        return $this->searchCriteria;
    }

    /**
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return $this
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function setSearchCriteria(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        $this->searchCriteria = $searchCriteria;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalCount()
    {
        $searchDatas = array(
            'KEY_NAME' => '',
            'KEY_NAME_KANA' => '',
            'KEY_CUSTOMER_CODE' => '',
            'KEY_EMAIL' => '',
            'KEY_POSTAL_CODE' => '',
            'KEY_ADDRESS' => '',
            'KEY_PHONE_NUMBER' => '',
            'KEY_COM_PH_NUM' => '',
            'KEY_COM_NAME' => '',
            'KEY_CUSTOMER_GROUP' => ''
        );

        $isSearch = false;
        foreach($searchDatas as $keySearch => $keyValue){
            $columnKeySearch = $this->_mappingAttribute[$keySearch];
            if(null != $this->getFilter($columnKeySearch)){
                $isSearch = true;
                $searchDatas[$keySearch] = stripslashes(trim(trim($this->getFilter($columnKeySearch)->getValue(),"%")));
            }
        }

        $items = array();
        if($isSearch){
            $items  = $this->searchConsumerDB($searchDatas);
        }

        //sort order
        $orders = $this->_orders;
        if($orders){
            $orderKey = key($orders);
            $orderDirection = $orders[$orderKey];
            if('ASC' == $orderDirection){
                $orderDirection = SORT_ASC;
            }
            else if('DESC' == $orderDirection){
                $orderDirection = SORT_DESC;
            }
            if($orderKey != '' && $orderDirection != ''){
                $this->array_sort_by_column($items, $orderKey ,$orderDirection );
            }
        }

        $checkIsSearchId = false;
        $searchId = 0;
        if(null != $this->getFilter('id') && "" != $this->getFilter('id')->getValue()){
            $checkIsSearchId = true;
            $searchId = (int)trim($this->getFilter('id')->getValue(),"%");
        }

        foreach($items as &$item){
            $itemObject = new \Magento\Framework\DataObject();
            $itemAttributes = array();
            if($checkIsSearchId){
                if(isset($item['id']) && $item['id'] != $searchId){
                    continue;
                }
            }
            foreach($item as $attributeCode => $attributeValue){
                $attribute = new \Magento\Framework\DataObject();
                $attribute->setData('attribute_code',$attributeCode);
                $attribute->setData('value',$attributeValue);
                $itemAttributes[] = $attribute;
            }

            $itemObject->setData('custom_attributes',$itemAttributes);
            $this->addItem($itemObject);
        }

        if (!$this->totalCount) {
            $this->totalCount = $this->getSize();
        }
        return $this->totalCount;
    }

    public function array_sort_by_column(&$arr, $col, $dir = SORT_ASC) {
        $sortCol = array();
        foreach ($arr as $key=> $row) {
            $sortCol[$key] = $row[$col];
        }

        array_multisort($sortCol, $dir, $arr);
    }


    /**
     * @param int $totalCount
     * @return $this
     */
    public function setTotalCount($totalCount)
    {
        $this->totalCount = $totalCount;
        return $this;
    }

    /**
     * Set items list.
     *
     * @param Document[] $items
     * @return $this
     */
    public function setItems(array $items = null)
    {
        if ($items) {
            foreach ($items as $item) {
                $this->addItem($item);
            }
            unset($this->totalCount);
        }
        return $this;
    }

    /**
     * Search Consumer DB
     *
     * @param $searchDatas
     *
     * @return array
     */
    public function searchConsumerDB($searchDatas){

        $now = $this->_dateTime->date('Y/m/d H:m:i');

        $soapConfig = $this->soapHelper->getCommonRequestParams();

        $wsdl = $this->_apiConsumerHelper->getConsumerApiUrl('/SelectCustomerService?wsdl');
        $endPoint = $this->_apiConsumerHelper->getConsumerApiUrl('/SelectCustomerService.SelectCustomerServiceHttpSoap12Endpoint/');
        $param1 = $this->_scopeConfig->getValue('consumer_db_api_url/customer/param1', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);
        $param2 = $this->_scopeConfig->getValue('consumer_db_api_url/customer/param2', \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

        $soapClient = $this->soapClientFactory->create($wsdl, $soapConfig);
        $soapClient->setLocation($endPoint);

        $params = array();
        $params[] = new \SoapVar($param1, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($param2, XSD_STRING, null, null, 'clientInfo' );
        $params[] = new \SoapVar($now, XSD_STRING, null, null, 'clientInfo' );

        $paramsSearchKey = array();
        $paramsSearchValue = array();

        foreach($searchDatas as $searchKey => $searchValue){
            if(trim($searchValue) !== ''){
                $paramsSearchKey[] = new \SoapVar($searchKey, XSD_STRING, null, null, 'array' );
                $paramsSearchValue[] = new \SoapVar($searchValue, XSD_STRING, null, null, 'array' );
            }
        }
        $params[] = new \SoapVar($paramsSearchKey, SOAP_ENC_ARRAY, null, null, 'requestParms' );
        $params[] = new \SoapVar($paramsSearchValue, SOAP_ENC_ARRAY, null, null, 'requestParms' );

        $response = array();
        try{
            $response = $soapClient->SelectCustomerRecept( new \SoapVar($params, SOAP_ENC_OBJECT) );
        } catch (\Exception $e) {
            $this->_logger->error((string)$e->getMessage());
            return array();
        }

        if(property_exists($response,'return')){
            $codeReturn = $response->return;

            if(isset($codeReturn[0]) && \Riki\Customer\Model\SsoConfig::SSO_RESPONSE_SUCCESS_CODE == $codeReturn[0]->array[0]){
                $i = 4;
                $customers = array();
                $id = 1;
                while(true){

                    if(!isset($response->return[$i])){
                        break;
                    }
                    $customer = array();
                    $customersKey = $response->return[3]->array;
                    $customersValue = $response->return[($i)]->array;

                    $customer['id'] = $id;
                    foreach($customersKey as $key => $customerKey){
                        if(isset($this->_mappingAttribute[$customerKey])){
                            $customer[$this->_mappingAttribute[$customerKey]] = $customersValue[$key];
                        }
                    }

                    $customers[] = $customer;
                    $i++;
                    $id++;
                }

                $request = $soapClient->getLastRequest();
                $this->_apiLogger->saveAPILog('selectCustomer',"Search Customer ConsumerDB",1,$request,$customers);

                return $customers;
            }
            else{
                $request = $soapClient->getLastRequest();
                $this->_apiLogger->saveAPILog('selectCustomer',"Search Customer ConsumerDB",0,$request,array());
                return array();
            }
        }
        else{
            $request = $soapClient->getLastRequest();
            $this->_apiLogger->saveAPILog('selectCustomer',"Search Customer ConsumerDB",0,$request,array());
            return array();
        }
    }


}