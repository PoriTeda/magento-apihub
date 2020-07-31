<?php

namespace Riki\StockPoint\Model\Api;

use Magento\Framework\Exception\FileSystemException;
use Riki\StockPoint\Api\BuildStockPointPostDataInterface;
use Magento\Framework\Exception\LocalizedException;
use \Riki\StockPoint\Logger\StockPointLogger;

class BuildStockPointPostData implements BuildStockPointPostDataInterface
{
    const PATH_PRIVATE_KEY = 'stock_point/private_key';

    const PATH_PUBLIC_KEY = 'stock_point/public_key';

    const KEY_AUTHORIZATION_API = 'subscriptioncourse/stockpoint/key_authorization_api';

    const URL_POST_SHOW_MAP = 'subscriptioncourse/stockpoint/post_url';

    const URL_REMOVE_BUCKET_ID = 'subscriptioncourse/stockpoint/url_api_remove_bucket_id';

    const URL_REGISTER_DELIVERY_API = 'subscriptioncourse/stockpoint/url_api_register_delivery';
    
    const URL_UPDATE_DELIVERY_API = 'subscriptioncourse/stockpoint/url_api_update_delivery';

    const URL_GET_DISCOUNT_RATE = 'subscriptioncourse/stockpoint/url_api_get_discount_rate';

    const URL_CONFIRM_BUCKET_ORDER = 'subscriptioncourse/stockpoint/url_api_confirm_bucket_order';

    const URL_GET_STOCK_POINT_DELIVERY_STATUS = 'subscriptioncourse/stockpoint/url_api_get_stock_point_delivery_status';

    const URL_DEACTIVATE_STOCK_POINT = 'subscriptioncourse/stockpoint/url_api_deactivate_stock_point';

    const URL_STOP_STOCK_POINT = 'subscriptioncourse/stockpoint/url_api_stop_stock_point';

    const LIFE_TIME_STOCK_POINT = 'subscriptioncourse/stockpoint/lifetime_stock_point_session';

    const ENCODE_SIGNATURE_ALG = 'sha256WithRSAEncryption';

    const LOCKER = 1;

    const PICKUP = 2;

    const DROPOFF = 3;

    const SUBCARRIER = 4;

    const LENGTH_RANDOM_NONCE = 32;

    /**
     * @var string
     */
    private $privateKey;
    /**
     * @var string
     */
    private $publicKey;

    /**
     * @var bool
     */
    protected $isRequestStockPoint =false;

    /**
     * @var string
     */
    protected $b64DataValue;
    /**
     * @var string
     */
    protected $b64SigValue;
    /**
     * @var string
     */
    protected $rawReqDataValue;
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $fileSystem;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var string
     */
    protected $dataNotifyConvert;

    /**
     * @var string
     */
    protected $signConvert;

    /**
     * @var string
     */
    protected $rikiStockId;

    /**
     * @var bool
     */
    protected $isVerifyPublicKeySuccess =false;

    /**
     * @var \Riki\StockPoint\Model\StockPointRepository
     */
    protected $stockPointRepository;
    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $httpClientFactory;
    /**
     * @var \Riki\Customer\Helper\Region
     */
    protected $regionHelper;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var bool
     */
    protected $isCallApiSuccess = false;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    /**
     * @var \Riki\StockPoint\Logger\StockPointLogger
     */
    protected $stockPointLogger;
    /**
     * @var string
     */
    protected $magentoDataNonce;
    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $randomString;

    /**
     * @var \Magento\Framework\Filesystem\File\ReadFactory
     */
    protected $fileReadFactory;

    /**
     * @var \Magento\Framework\App\DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * @var \Riki\TimeSlots\Helper\Data
     */
    protected $helperTimeSlot;

    /**
     * BuildStockPointPostData constructor.
     * @param \Magento\Framework\Filesystem $fileSystem
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Riki\StockPoint\Model\StockPointRepository $stockPointRepository
     * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
     * @param \Riki\Customer\Helper\Region $regionHelper
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param StockPointLogger $stockPointLogger
     * @param \Magento\Framework\Math\Random $randomString
     * @param \Magento\Framework\Filesystem\File\ReadFactory $fileReadFactory
     * @param \Magento\Framework\App\DeploymentConfig $deploymentConfig
     */
    public function __construct(
        \Magento\Framework\Filesystem $fileSystem,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\StockPoint\Model\StockPointRepository $stockPointRepository,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Riki\Customer\Helper\Region $regionHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        StockPointLogger $stockPointLogger,
        \Magento\Framework\Math\Random $randomString,
        \Magento\Framework\Filesystem\File\ReadFactory $fileReadFactory,
        \Magento\Framework\App\DeploymentConfig $deploymentConfig,
        \Riki\TimeSlots\Helper\Data $helperTimeSlot
    ) {
        $this->fileSystem = $fileSystem;
        $this->scopeConfig = $scopeConfig;
        $this->stockPointRepository = $stockPointRepository;
        $this->httpClientFactory = $httpClientFactory;
        $this->regionHelper = $regionHelper;
        $this->timezone = $timezone;
        $this->messageManager = $messageManager;
        $this->stockPointLogger = $stockPointLogger;
        $this->randomString = $randomString;
        $this->fileReadFactory = $fileReadFactory;
        $this->deploymentConfig = $deploymentConfig;
        $this->helperTimeSlot = $helperTimeSlot;
    }

    /**
     * Get value of sign value
     *
     * @return mixed
     */
    public function getSignValue()
    {
        return $this->b64SigValue;
    }

    /**
     * Check request stock point notify
     *
     * @return bool
     */
    public function isRequestStockPointNotify()
    {
        return $this->isRequestStockPoint;
    }

    /**
     * Check call api success or fail
     *
     * @return bool
     */
    public function checkCallApiSuccess()
    {
        return $this->isCallApiSuccess;
    }

    /**
     * Set data for post data request
     *
     * @param $rawDataValue
     * @return mixed|void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function setPostDataRequest($rawDataValue)
    {
        $rawDataValue['sectime'] = date(DATE_ISO8601);
        $this->magentoDataNonce = $this->randomString->getRandomString(self::LENGTH_RANDOM_NONCE);
        $rawDataValue['magento_data']['nonce'] = $this->magentoDataNonce;
        $this->buildDataWithOpenSSL($rawDataValue);
    }

    /**
     * Get post data request
     *
     * @return string
     */
    public function getPostDataRequestGenerate()
    {
        return $this->rawReqDataValue;
    }

    /**
     * Get data value
     *
     * @param $path
     * @return mixed
     * @throws FileSystemException
     */
    public function getDataFile($path)
    {
        $read = $this->fileReadFactory->create($path, \Magento\Framework\Filesystem\DriverPool::FILE);
        return $read->readAll();
    }

    /**
     * Get public key
     *
     * @return mixed
     * @throws FileSystemException
     */
    protected function getPublicKey()
    {
        $path = $this->deploymentConfig->get(self::PATH_PUBLIC_KEY);
        if ($path != null) {
            $this->publicKey = $this->getDataFile($path);
        }
        return $this->publicKey;
    }

    /**
     * Get private key
     *
     * @return null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getPrivateKey()
    {
        $path = $this->deploymentConfig->get(self::PATH_PRIVATE_KEY);
        if ($path != null) {
            $this->privateKey = $this->getDataFile($path);
        }
        return $this->privateKey;
    }

    /**
     * Build data call curl
     *
     * @param $rawDataValue
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function buildDataWithOpenSSL($rawDataValue)
    {
        /**
         * Get private key
         */
        $dataPrivateKey = $this->getPrivateKey();

        /**
         * Build {{DATA_VALUE}}
         */
        $rawDataValue = json_encode($rawDataValue);
        $this->b64DataValue = base64_encode($rawDataValue);
        $privateKeyId = openssl_pkey_get_private($dataPrivateKey);
        openssl_sign(
            $this->b64DataValue,
            $rawSigValue,
            $privateKeyId,
            self::ENCODE_SIGNATURE_ALG
        );
        openssl_free_key($privateKeyId);

        /**
         * Build  {{SIG_VALUE}}
         */
        $this->b64SigValue = base64_encode($rawSigValue);
        $rawReqDataValue = [
            'data' => $this->b64DataValue,
            'sig' => $this->b64SigValue
        ];
        $rawReqDataValue = json_encode($rawReqDataValue);
        $this->rawReqDataValue = base64_encode($rawReqDataValue);
        return $this->rawReqDataValue;
    }

    /**
     * Get data notify convert
     *
     * @return array|mixed|string
     */
    public function getDataNotifyConvert()
    {
        if (!is_array($this->dataNotifyConvert) && $this->dataNotifyConvert != '') {
            $result = json_decode($this->dataNotifyConvert, true);
            if ($result) {
                if (isset($result['next_delivery_date'])) {
                    $result['next_delivery_date'] = str_replace('/', '-', $result['next_delivery_date']);
                }
                if (isset($result['next_order_date'])) {
                    $result['next_order_date'] = str_replace('/', '-', $result['next_order_date']);
                }
                $this->dataNotifyConvert  = $result;
            } else {
                $this->dataNotifyConvert  = false;
            }
        }

        return $this->dataNotifyConvert;
    }

    /**
     * Check public key success or fail
     *
     * @return bool
     * If success = true,false = faile
     */
    public function isVerifyPublicKeySuccess()
    {
        return  $this->isVerifyPublicKeySuccess;
    }

    /**
     * Check data notify map select
     *
     * @param $b64ReqDataValue
     * @param $profileData
     * @return mixed|void
     * @throws LocalizedException
     */
    public function checkDataNotifyMapSelected($b64ReqDataValue, $profileData)
    {
        if (isset($b64ReqDataValue['reqdata']) && $b64ReqDataValue['reqdata'] != '') {
            $receiveData = json_decode(base64_decode($b64ReqDataValue['reqdata']), true);
            if (isset($receiveData['data']) && isset($receiveData['sig'])) {
                $this->isRequestStockPoint = true;

                $this->signConvert = $receiveData['sig'];
                $publicKey = $this->getPublicKey();
                $publicKeyId = openssl_pkey_get_public($publicKey);
                $verifyResult = openssl_verify(
                    $receiveData['data'],
                    base64_decode($this->signConvert),
                    $publicKeyId,
                    self::ENCODE_SIGNATURE_ALG
                );
                openssl_free_key($publicKeyId);

                try {
                    if ($verifyResult && $profileData) {
                        $this->dataNotifyConvert = base64_decode($receiveData['data']);
                        $this->prepareNotifiedDataFromShowMap();
                        /** verify nonce data */
                        $rikiNonce = $profileData->getData('riki_stock_point_nonce');
                        if ($rikiNonce && $this->verifyNonceData($rikiNonce)) {
                            /**
                              * Save data stock point when save success
                              */
                            $this->saveDataStockPointValidateSuccess();
                            return true;
                        }
                    } elseif (!$verifyResult) {
                        $logData = [
                            'label' => 'SAML verification is failed',
                            'profile_id' => $profileData->getData('profile_id')
                        ];
                        $this->stockPointLogger->info(
                            json_encode($logData),
                            ["type" => StockPointLogger::LOG_TYPE_NOTIFY_DATA_SHOW_MAP]
                        );
                    }
                } catch (\Exception $e) {
                    $this->isVerifyPublicKeySuccess = false;
                    return false;
                }
            }
        }
    }

    /**
     * Get region name by id
     *
     * @param $regionId
     * @return null
     */
    public function getRegionNameById($regionId)
    {
        $regionName = $this->regionHelper->getJapanRegion($regionId);
        if ($regionName) {
            return $regionName;
        }
        return null;
    }

    /**
     * Save data for stock point
     *
     * @return bool|string
     */
    public function saveDataStockPointValidateSuccess()
    {
        $data = $this->getDataNotifyConvert();
        if ($data) {
            /**
             * Convert prefecture name to region id
             */
            if (isset($data['stock_point_prefecture']) && $data['stock_point_prefecture'] !='') {
                $regionId = $this->regionHelper->getRegionIdByName(trim($data['stock_point_prefecture']));
                if ($regionId) {
                    $data['stock_point_prefecture'] = $regionId;
                } else {
                    $logData = [
                        'label' => 'The region is not valid',
                        'profile_id' => $data['magento_data']['profile_id'],
                        'stock_point_region' => $data['stock_point_prefecture']
                    ];
                    $this->stockPointLogger->info(
                        json_encode($logData),
                        ["type" => StockPointLogger::LOG_TYPE_NOTIFY_DATA_SHOW_MAP]
                    );
                    return false;
                }
            }
            $this->rikiStockId = $this->stockPointRepository->saveAndReturnStockPointId($data);
            if ($this->rikiStockId) {
                /**
                 * This is flag for check call api success or fail
                 */
                $this->isVerifyPublicKeySuccess = true;
            }
            return $this->rikiStockId;
        }
        return false;
    }

    /**
     * Get riki stock point id
     *
     * @return int
     */
    public function getRikiStockId()
    {
        return $this->rikiStockId;
    }

    /**
     * Get url post map
     *
     * @return string
     */
    public function getUrlPostMap()
    {
        return $this->getUrlConfigCallApi(self::URL_POST_SHOW_MAP);
    }

    /**
     * Get key authorization
     *
     * @return mixed|string
     */
    public function getKeyAuthorization()
    {
        $data = $this->scopeConfig->getValue(self::KEY_AUTHORIZATION_API);
        if ($data != null) {
            return $data;
        }
    }

    /**
     * Get url config
     *
     * @param $path
     * @return mixed
     */
    public function getUrlConfigCallApi($path)
    {
        $data = $this->scopeConfig->getValue($path);
        if ($data != null) {
            return $data;
        }
    }

    /**
     * Convert delivery delivery type stock point
     *
     * @param $deliveryType
     * @return int
     */
    public function convertDeliveryTypeStockPoint($deliveryType)
    {
        if ($deliveryType == 'locker') {
            return BuildStockPointPostData::LOCKER;
        }

        if ($deliveryType == 'dropoff') {
            return BuildStockPointPostData::DROPOFF;
        }

        if ($deliveryType == 'pickup') {
            return BuildStockPointPostData::PICKUP;
        }

        if ($deliveryType == 'subcarrier') {
            return BuildStockPointPostData::SUBCARRIER;
        }
    }

    /**
     * Call api by curl
     *
     * @param $pathUrlConfig
     * @param $requestData
     * @return array
     */
    public function getCurlApi($pathUrlConfig, $requestData)
    {
        /**
         * Convert data to json
         */
        if (is_array($requestData)) {
            $requestData = json_encode($requestData);
        }

        $dataReturn = [
            'call_api'=> 'fail',
            'data'=>null
        ];
        try {
            /** @var \Magento\Framework\HTTP\ZendClient $client */
            $client = $this->httpClientFactory->create();
            $client->setUri($pathUrlConfig);
            $client->setRawData($requestData, 'application/json');
            $client->setHeaders(
                [
                    'Authorization' =>  $this->getKeyAuthorization(),
                ]
            );

            $response = $client->request(\Zend_Http_Client::POST)->getBody();
            if ($response) {
                $data = json_decode($response, true);
                if (isset($data['errorCode'])) {
                    $this->isCallApiSuccess = false;
                    $dataReturn = [
                        'call_api'=> 'fail',
                        'data'=> $data
                    ];
                } else {
                    $this->isCallApiSuccess = true;
                    $dataReturn = [
                        'call_api'=> 'success',
                        'data'=>json_decode($response, true),
                    ];
                }
            }
        } catch (\Exception $e) {
            $this->isCallApiSuccess = false;
            $dataReturn = [
                'call_api'=> 'fail',
                'data'=>$e->getMessage(),
            ];
        }
        return $dataReturn;
    }

    /**
     * Get register delivery for stock point
     *
     * @param $requestData
     * @return null
     */
    public function callAPIRegisterDelivery($requestData)
    {
        $pathUrlConfig = $this->getUrlConfigCallApi(self::URL_REGISTER_DELIVERY_API);
        $data = $this->getCurlApi($pathUrlConfig, $requestData);

        $logData = [
            'label' => 'Call API Register Delivery',
            'request_data' => $requestData,
            'return_data'  => $data
        ];
        $this->stockPointLogger->info(json_encode($logData), ['type'=> StockPointLogger::LOG_TYPE_REGISTER_DELIVERY]);
        return $data;
    }
    
    /**
     * call API to update delivery
     *
     * @param $requestData
     * @return null
     */
    public function callAPIUpdateDelivery($requestData)
    {
        $pathUrlConfig = $this->getUrlConfigCallApi(self::URL_UPDATE_DELIVERY_API);
        $this->stockPointLogger->info("NET-18-URL " .  json_encode($pathUrlConfig), ['type'=> StockPointLogger::LOG_TYPE_UPDATE_DELIVERY]);
        $this->stockPointLogger->info("NET-18-Request-Data " . json_encode($requestData), ['type'=> StockPointLogger::LOG_TYPE_UPDATE_DELIVERY]);
        $data = $this->getCurlApi($pathUrlConfig, $requestData);

        $logData = [
            'label' => 'Call API Update Delivery',
            'request_data' => $requestData,
            'return_data'  => $data
        ];
        $this->stockPointLogger->info(json_encode($logData), ['type'=> StockPointLogger::LOG_TYPE_UPDATE_DELIVERY]);
        return $data;
    }

    /**
     * Remove subscription profile from bucket
     *
     * @param $profileId
     * @return array
     */
    public function removeFromBucket($profileId)
    {
        $requestData= [
            "profile_id" => $profileId
        ];

        $pathUrlConfig = $this->getUrlConfigCallApi(self::URL_REMOVE_BUCKET_ID);
        $data = $this->getCurlApi($pathUrlConfig, $requestData);

        $logData = [
            'label' => "Call API Remove From Bucket - Profile Id :"  . $profileId,
            'return_data' => $data
        ];
        $this->stockPointLogger->info(
            json_encode($logData),
            ["type"=> StockPointLogger::LOG_TYPE_REMOVE_FROM_BUCKET]
        );

        if (isset($data['call_api']) && $data['call_api'] == 'success') {
            $dataReturn = $data['data'];
            if (isset($dataReturn['result'])) {
                return [
                    'success'=>true
                ];
            }

            if (isset($dataReturn['errorCode'])) {
                return [
                    'success'=>false,
                    'message'=>$dataReturn['message']
                ];
            }
        }

        return [
            'success'=>false
        ];
    }

    /**
     * Get Discount Rate from Stock Point
     *
     * @param $requestData
     * @return array
     */
    public function callApiGetDiscountRate($requestData)
    {
        $pathUrlConfig = $this->getUrlConfigCallApi(self::URL_GET_DISCOUNT_RATE);
        $data = $this->getCurlApi($pathUrlConfig, $requestData);

        $logData = [
            'label' => 'Call API GetDiscountRate',
            'request_data' => $requestData,
            'return_data' => $data
        ];
        $this->stockPointLogger->info(
            json_encode($logData),
            ["type" => StockPointLogger::LOG_TYPE_DISCOUNT_RATE]
        );

        return $data;
    }

    /**
     * Send actual order created info to StockPoint system,
     *
     * @param $requestData
     * @return array
     */
    public function callApiConfirmBucketOrder($requestData)
    {
        $pathUrlConfig = $this->getUrlConfigCallApi(self::URL_CONFIRM_BUCKET_ORDER);
        $data = $this->getCurlApi($pathUrlConfig, $requestData);

        $logData = [
            'label' => 'Call API Confirm bucket Order Output',
            'request_data' => $requestData,
            'return_data' => $data
        ];
        $this->stockPointLogger->info(
            json_encode($logData),
            ["type" => StockPointLogger::LOG_TYPE_CONFIRM_BUCKET_ORDER]
        );
        return $data;
    }

    /**
     * Provide delivery status from StockPoint to Customer
     *
     * @param $requestData
     * @return array
     */
    public function callApiGetStockPointDeliveryStatus($requestData)
    {
        $pathUrlConfig = $this->getUrlConfigCallApi(self::URL_GET_STOCK_POINT_DELIVERY_STATUS);
        $data = $this->getCurlApi($pathUrlConfig, $requestData);

        $logData = [
            'label' => 'Call API Get Stock Point Delivery Status',
            'request_data' => $requestData,
            'return_data' => $data
        ];
        $this->stockPointLogger->info(
            json_encode($logData),
            ["type" => StockPointLogger::LOG_TYPE_STOCK_POINT_DELIVERY_STATUS]
        );

        return $data;
    }

    /**
     * Synchronize deactivated StockPoint  info to Magento
     *
     * @param $requestData
     * @return array
     */
    public function callApiDeactivateStockPoint($requestData)
    {
        $pathUrlConfig = $this->getUrlConfigCallApi(self::URL_DEACTIVATE_STOCK_POINT);
        $data = $this->getCurlApi($pathUrlConfig, $requestData);
        return $data;
    }
    /**
     * synchronize deactivated StockPoint  info to Magento
     *
     * @param $requestData
     * @return array
     */
    public function callApiStopStockPoint($requestData)
    {
        $pathUrlConfig = $this->getUrlConfigCallApi(self::URL_STOP_STOCK_POINT);
        $data = $this->getCurlApi($pathUrlConfig, $requestData);
        return $data;
    }
    /**
     * Get discount rate
     *
     * @param $profileId
     * @param $deliveryDate
     * @return int
     */
    public function getDiscountRate($profileId, $deliveryDate)
    {
        $apiResponse = $this->callApiGetDiscountRate(
            [
                'profile_id' => $profileId,
                'delivery_date' => $deliveryDate
            ]
        );

        if ($apiResponse['call_api'] == 'success') {
            if (isset($apiResponse['data']['discount_rate'])) {
                return (int)$apiResponse['data']['discount_rate'];
            }
        }

        return 0;
    }

    /**
     * Set data profile for stock point
     *
     * @param $profileSession
     * @param $profileId
     * @return mixed
     * @throws \Zend_Json_Exception
     */
    public function setDataStockPointToProfile($profile, $profileId)
    {
        $stockPointData = $this->getDataNotifyConvert();

        if (!empty($stockPointData) && $profile) {
            $stockPointData["delivery_type"] = $this->convertDeliveryTypeStockPoint($stockPointData["delivery_type"]);

            $profile->setData("stock_point_data", $stockPointData);
            $profile->setData("frequency_unit", $stockPointData["frequency_unit"]);
            $profile->setData("frequency_interval", $stockPointData["frequency_interval"]);
            $profile->setData("next_delivery_date", $stockPointData["next_delivery_date"]);
            $profile->setData("next_order_date", $stockPointData["next_order_date"]);
            $profile->setData("stock_point_delivery_information", $stockPointData["comment_for_customer"]);
            $profile->setData("stock_point_delivery_type", $stockPointData["delivery_type"]);

            $rikiStockPointId = $profile->getData('riki_stock_point_id');
            if ($this->getRikiStockId()) {
                $rikiStockPointId = $this->getRikiStockId();
            }
            $profile->setData("riki_stock_point_id", $rikiStockPointId);
            $profile->setData("frequency_unit", $stockPointData["frequency_unit"]);
            $profile->setData("frequency_interval", $stockPointData["frequency_interval"]);
            $profile->setData("next_delivery_date", $stockPointData["next_delivery_date"]);
            $profile->setData("next_order_date", $stockPointData["next_order_date"]);
            $profile->setData("stock_point_delivery_information", $stockPointData["comment_for_customer"]);
            $profile->setData("stock_point_delivery_type", $stockPointData["delivery_type"]);

            /**
             * Set data product cart
             */
            foreach ($profile["product_cart"] as $product) {
                /** save origin delivery date */
                $product->setData('original_delivery_date', $product->getData('delivery_date'));
                $product->setData('original_delivery_time_slot', $product->getData('delivery_time_slot'));

                $product->setData('delivery_date', $stockPointData['next_delivery_date']);
                $product->setData('stock_point_discount_rate', $stockPointData['current_discount_rate']);
                $product->setData('delivery_time_slot', $stockPointData['delivery_time']);
            }

            /**
             * Set data to current profile
             */
            $this->messageManager->addSuccessMessage(
                __("Stock Point temporarily added. please click \"Confirm Changes\" button to add actually")
            );
        }

        return $profile;
    }

    /**
     * Format date
     *
     * @param $valueSearch
     * @param $valueReplace
     * @param $data
     * @return mixed
     */
    public function formatDate($valueSearch, $valueReplace, $data)
    {
        $dataReplace =  str_replace($valueSearch, $valueReplace, $data);
        return $dataReplace;
    }

    /**
     * Convert date before call api
     *
     * @param $data
     * @return mixed
     */
    public function convertDataBeforeCallApi($data)
    {
        if (isset($data['next_order_date'])) {
            $data['next_order_date'] = $this->formatDate('-', '/', $data['next_order_date']);
        }

        if (isset($data['next_delivery_date'])) {
            $data['next_delivery_date'] = $this->formatDate('-', '/', $data['next_delivery_date']);
        }
        return $data;
    }

    /**
     * Get none data for post show map
     *
     * @return string
     */
    public function getNonceData()
    {
        return $this->magentoDataNonce;
    }

    /**
     * Verify data of param nonce on post show map
     *
     * @param $nonceDataOnProfileSession
     * @return bool
     */
    public function verifyNonceData($nonceDataOnProfileSession)
    {
        $data = $this->getDataNotifyConvert();

        if ($data) {
            if (isset($data['magento_data']) && isset($data['magento_data']['nonce'])) {
                if ($nonceDataOnProfileSession == $data['magento_data']['nonce']) {
                    return true;
                }
            }
        }

        $logData = [
            'label' => 'Verify nonce data is failed',
            'profile_id' => $data['magento_data']['profile_id']
        ];
        $this->stockPointLogger->info(
            json_encode($logData),
            ["type" => StockPointLogger::LOG_TYPE_NOTIFY_DATA_SHOW_MAP]
        );

        return false;
    }

    /**
     * @return bool
     */
    public function verifySecTime()
    {
        $data = $this->getDataNotifyConvert();
        if ($data) {
            if (isset($data['sectime']) && $data['sectime'] !='') {
                $nowTimezone = date(DATE_ISO8601);
                $currentDate = strtotime($nowTimezone);
                $lifeTime = $this->getUrlConfigCallApi(self::LIFE_TIME_STOCK_POINT);
                /** default 20 minute */
                if ((int)$lifeTime<=0) {
                    $lifeTime = 20;
                }
                $secTimeDate = strtotime($data['sectime']." + $lifeTime minute");
                if ($secTimeDate>=$currentDate) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Validate request data stock point
     * @param array $params
     * @param object $profileSession
     * @return array
     * @throws LocalizedException
     */
    public function validateRequestStockPoint($params, $profileSession)
    {
        $this->checkDataNotifyMapSelected($params, $profileSession);
        $isRequestStockPoint = $this->isRequestStockPointNotify();
        $isVerifyStockPoint  = $this->isVerifyPublicKeySuccess();
        if ($isRequestStockPoint) {
            if (!$isVerifyStockPoint) {
                /**
                 * Stock point
                 * If SIG_VALUE is NOT matching with Magento value, then the system will show error message.
                 */
                $result = [
                    'status'=> false,
                    'message' => __('There are something wrong in the system. Please re-try again')
                ];
                return $result;
            }
            $isVerifySectime  = $this->verifySecTime();
            if (!$isVerifySectime) {
                $result = [
                    'status'=> false,
                    'message' => __('The session has been timeout. Please re-try again.')
                ];
                return $result;
            }
            if (!isset($this->getDataNotifyConvert()["magento_data"]["profile_id"])) {
                $result = [
                    'status'=> false,
                    'message' => __('There are something wrong in the system. Please re-try again')
                ];
                return $result;
            }
        }
        $result = [
            'status'=> true,
            'message' => null
        ];
        return $result;
    }

    /**
     * Validate data notify from stock point show map.Only validate time slot if the delivery time is not empty.
     *
     * @return void
     */
    private function prepareNotifiedDataFromShowMap()
    {
        $data = $this->getDataNotifyConvert();
        if ($data) {
            if (isset($data['delivery_time']) && $data['delivery_time']) {
                $isTimeSlot = $this->validateDeliveryTimeSlot($data['delivery_time']);
                if (!$isTimeSlot) {
                    /**
                     * If the value delivery time slot does not match on magento, set delivery_time = null
                     */
                    $this->dataNotifyConvert['delivery_time'] = null;
                }
            } else {
                $this->dataNotifyConvert['delivery_time'] = null;
            }
        }
    }

    /**
     * Validate delivery time slot
     *
     * @param $deliveryTimeSlot
     * @return bool
     */
    public function validateDeliveryTimeSlot($deliveryTimeSlot)
    {
        $timeSlot = $this->helperTimeSlot->_getTimeSlotFromCollectionById($deliveryTimeSlot);
        if ($timeSlot) {
            return $deliveryTimeSlot;
        } else {
            $data = [
                'delivery_time_slot' => $deliveryTimeSlot,
                'message' => 'The delivery time slot is not valid',
            ];
            $this->stockPointLogger->info(
                json_encode($data),
                ["type" => StockPointLogger::LOG_TYPE_NOTIFY_DATA_SHOW_MAP]
            );
        }
        return false;
    }
}
