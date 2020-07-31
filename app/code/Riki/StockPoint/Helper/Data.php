<?php

namespace Riki\StockPoint\Helper;

use Magento\Framework\App\Helper\Context;
use Riki\Subscription\Helper\Order\Data as SubscriptionOrderDataHelper;
use Riki\Subscription\Model\ProductCart\ProductCart;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_PATH_SFTP_HOST = 'setting_sftp/setup_ftp/ftp_id';
    const CONFIG_PATH_SI_SFTP_PORT = 'setting_sftp/setup_ftp/ftp_port';
    const CONFIG_PATH_SI_SFTP_USER = 'setting_sftp/setup_ftp/ftp_user';
    const CONFIG_PATH_SI_SFTP_PASS = 'setting_sftp/setup_ftp/ftp_pass';
    const CONFIG_PATH_STOCK_POINT_LOCATION = 'subscriptioncourse/stockpoint/stock_point_csv_file_location_on_sftp';
    const CONFIG_PATH_LIMIT_NUMBER_SUB_PROFILES = 'subscriptioncourse/stockpoint/limit_number_sub_profiles';
    const CONFIG_PATH_GOOGLE_MAP_API_KEY = 'subscriptioncourse/stockpoint/google_map_api_key';
    const CONFIG_PATH_STOCK_POINT_EMAIL_ENABLE = 'subscriptioncourse/stockpoint_email/enable';
    const CONFIG_PATH_STOCK_POINT_EMAIL_SENDER = 'subscriptioncourse/stockpoint_email/sender';
    const CONFIG_PATH_STOCK_POINT_EMAIL_TEMPLATE = 'subscriptioncourse/stockpoint_email/email_template';

    const CONFIG_PATH_STOCK_POINT_PROXY_ENABLE = 'subscriptioncourse/stockpoint_proxy/enable';
    const CONFIG_PATH_STOCK_POINT_PROXY_HOST_NAME = 'subscriptioncourse/stockpoint_proxy/proxy_host_name';
    const CONFIG_PATH_STOCK_POINT_PROXY_PORT = 'subscriptioncourse/stockpoint_proxy/proxy_port';
    const CONFIG_PATH_STOCK_POINT_PROXY_USERNAME = 'subscriptioncourse/stockpoint_proxy/proxy_username';
    const CONFIG_PATH_STOCK_POINT_PROXY_PASSWORD = 'subscriptioncourse/stockpoint_proxy/proxy_password';

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    private $profileHelper;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    private $dataObject;

    /**
     * @var \Riki\AdvancedInventory\Helper\OutOfStock
     */
    private $outOfStockHelper;

    /**
     * @var array
     */
    protected $stockPointProfileOrderStatus = [];

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $csvReader;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    protected $curl;

    /**
     * @var \Riki\StockPoint\Api\Data\StockPointInterfaceFactory
     */
    protected $stockPointFactory;

    /**
     * @var \Riki\StockPoint\Model\StockPointProfileBucketFactory
     */
    protected $stockPointProfileBucketFactory;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslate;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Data constructor.
     * @param Context $context
     * @param \Riki\Subscription\Helper\Profile\Data $profileHelper
     * @param \Magento\Framework\DataObjectFactory $dataObject
     * @param \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\File\Csv $csvReader
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     * @param \Riki\StockPoint\Api\Data\StockPointInterfaceFactory $stockPointFactory
     * @param \Riki\StockPoint\Model\StockPointProfileBucketFactory $stockPointProfileBucketFactory
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslate
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        Context $context,
        \Riki\Subscription\Helper\Profile\Data $profileHelper,
        \Magento\Framework\DataObjectFactory $dataObject,
        \Riki\AdvancedInventory\Helper\OutOfStock $outOfStockHelper,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\File\Csv $csvReader,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Riki\StockPoint\Api\Data\StockPointInterfaceFactory $stockPointFactory,
        \Riki\StockPoint\Model\StockPointProfileBucketFactory $stockPointProfileBucketFactory,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslate,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {

        $this->profileHelper = $profileHelper;
        $this->dataObject = $dataObject;
        $this->outOfStockHelper = $outOfStockHelper;
        $this->encryptor = $encryptor;
        $this->csvReader = $csvReader;
        $this->curl = $curl;
        $this->stockPointFactory = $stockPointFactory;
        $this->stockPointProfileBucketFactory = $stockPointProfileBucketFactory;
        $this->inlineTranslate = $inlineTranslate;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->customerRepository = $customerRepository;

        parent::__construct($context);
    }

    /**
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @param \Magento\Catalog\Model\Product $product
     * @param \Riki\Subscription\Model\ProductCart\ProductCart|null $profileItem
     * @return \Magento\Catalog\Model\Product
     */
    public function initStockPointDataForProduct(
        \Riki\Subscription\Model\Profile\Profile $profile,
        \Magento\Catalog\Model\Product $product,
        \Riki\Subscription\Model\ProductCart\ProductCart $profileItem = null
    ) {
        if ($profileItem === null) {
            $profileItem = $this->getProfileItemByProduct($profile, $product);
        }

        if ($profileItem) {
            $discountRate = $profileItem->getData('stock_point_discount_rate');
        } else { // new spot product
            $discountRate = $this->getDefaultProfileDiscountRate($profile);
        }

        if ($profile->getData(SubscriptionOrderDataHelper::PROFILE_STOCK_POINT_BUCKET_ID)) {
            $product->setData(
                ProductCart::PROFILE_STOCK_POINT_DISCOUNT_RATE_KEY,
                $discountRate
            );

            $product->setData(
                SubscriptionOrderDataHelper::SUBSCRIPTION_PROFILE_ID_FIELD_NAME,
                $profile->getId()
            );
            $product->setData(
                SubscriptionOrderDataHelper::IS_STOCK_POINT_PROFILE,
                $profile->getData(SubscriptionOrderDataHelper::PROFILE_STOCK_POINT_BUCKET_ID) != false
            );

            $product->setData(
                ProductCart::IS_SIMULATOR_PROFILE_ITEM_KEY,
                true
            );
        }

        return $product;
    }

    /**
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @param \Magento\Catalog\Model\Product $product
     * @return null|\Riki\Subscription\Model\ProductCart\ProductCart
     */
    private function getProfileItemByProduct(
        \Riki\Subscription\Model\Profile\Profile $profile,
        \Magento\Catalog\Model\Product $product
    ) {
        $profileItems = $this->profileHelper->getProfileItemsByProfile($profile);

        /** @var \Riki\Subscription\Model\ProductCart\ProductCart $profileItem */
        foreach ($profileItems as $profileItem) {
            if ($profileItem->getProductId() == $product->getId()) {
                return $profileItem;
            }
        }

        return null;
    }

    /**
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @return int|mixed
     */
    private function getDefaultProfileDiscountRate(
        \Riki\Subscription\Model\Profile\Profile $profile
    ) {
        $profileItems = $this->profileHelper->getProfileItemsByProfile($profile);

        $discountRate = 0;

        /** @var \Riki\Subscription\Model\ProductCart\ProductCart $profileItem */
        foreach ($profileItems as $profileItem) {
            $discountRate = $profileItem->getData('stock_point_discount_rate');

            if ($discountRate) {
                break;
            }
        }

        return $discountRate;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    public function isStockPointProfileOrder(\Magento\Sales\Model\Order $order)
    {
        $orderId = $order->getId();

        if (!isset($this->stockPointProfileOrderStatus[$orderId])) {
            $isStockPointProfileOrder = false;

            if ($order->getStockPointDeliveryBucketId()) {
                $isStockPointProfileOrder = true;
            }

            if ($order->getSubscriptionProfileId()) {
                if ($outOfStock = $this->outOfStockHelper->getOosItemByGeneratedOrder($order->getId())) {
                    $originalOrder = $outOfStock->getOriginalOrder();

                    if ($originalOrder && $originalOrder->getStockPointDeliveryBucketId()) {
                        $isStockPointProfileOrder = true;
                    }
                }
            }

            $this->stockPointProfileOrderStatus[$orderId] = $isStockPointProfileOrder;
        }

        return $this->stockPointProfileOrderStatus[$orderId];
    }

    /**
     * Get config stock point location
     *
     * @return mixed
     */
    public function getStockPointLocation()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_STOCK_POINT_LOCATION,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get config limit number of sub profiles
     *
     * @return mixed
     */
    public function getLimitNumberSubProfiles()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_LIMIT_NUMBER_SUB_PROFILES,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get config google map api key
     *
     * @return mixed
     */
    public function getGoogleMapAPIKey()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_GOOGLE_MAP_API_KEY,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get config sftp host
     *
     * @return mixed
     */
    public function getSftpHost()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_SFTP_HOST,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get config sftp port
     *
     * @return mixed
     */
    public function getSftpPort()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_SI_SFTP_PORT,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get config sftp user
     *
     * @return mixed
     */
    public function getSftpUser()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_SI_SFTP_USER,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get config sftp pass
     *
     * @return mixed
     */
    public function getSftpPass()
    {
        $pass =  $this->scopeConfig->getValue(
            self::CONFIG_PATH_SI_SFTP_PASS,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
        return $this->encryptor->decrypt($pass);
    }

    /**
     * Get config stock point enable email
     *
     * @return mixed
     */
    public function getEmailEnable()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_STOCK_POINT_EMAIL_ENABLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get senders which send notify email
     *
     * @return mixed
     */
    public function getSenderEmail()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_STOCK_POINT_EMAIL_SENDER,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get email template
     *
     * @return mixed
     */
    public function getEmailTemplate()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_STOCK_POINT_EMAIL_TEMPLATE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get data from CSV file
     *
     * @param $filename
     * @return array
     */
    public function getCsvData($filename)
    {
        $contentFile = $this->csvReader->getData($filename);
        $data = [];
        if ($contentFile) {
            foreach ($contentFile as $content) {
                $data[] = array_map('trim', $content);
            }
        }

        return $data;
    }

    /**
     * Call API google map
     *
     * @param $address
     * @return boolean|array
     */
    public function callAPIGoogleMap($address)
    {
        // Url encode the address
        $apiKey = $this->getGoogleMapAPIKey();

        // Google map geocode api url
        $url = "https://maps.googleapis.com/maps/api/geocode/json?";

        try {
            $header = [
                'cache-control' => 'no-cache',
                'content-type' => 'application/json'
            ];

            $query = http_build_query([
                'address' => $address,
                'key' => $apiKey,
            ]);

            // If is using proxy
            if ($this->getStockPointProxyEnable()
                && trim($this->getStockPointProxyHostName())
                && 0 < $this->getStockPointProxyPort()
            ) {
                $proxies = [
                    CURLOPT_PROXY => $this->getStockPointProxyHostName(),
                    CURLOPT_PROXYPORT => $this->getStockPointProxyPort(),
                    CURLOPT_PROXYTYPE => 'HTTP',
                    CURLOPT_HTTPPROXYTUNNEL => 1
                ];

                if (trim($this->getStockPointProxyUsername()) && trim($this->getStockPointProxyPassword())) {
                    $proxyAuth = $this->getStockPointProxyUsername() . ':' . $this->getStockPointProxyPassword();
                    $proxies[CURLOPT_PROXYUSERPWD] = $proxyAuth;
                }

                // Set proxy setting for curl
                $this->curl->setOptions($proxies);
            }

            $this->curl->setHeaders($header);
            $this->curl->get($url . $query);

            $response = $this->curl->getBody();
            $response = json_decode($response, true);
        } catch (\Exception $e) {
            $response = [
                "error_message" => $e->getMessage(),
                'results' => [],
                'status' => "OTHER"
            ];
        }

        return $response;
    }

    /**
     * Calculate distance between 2 points
     *
     * @param $lat1
     * @param $lon1
     * @param $lat2
     * @param $lon2
     * @param $mode
     *
     * @return float
     */
    public function distance($lat1, $lon1, $lat2, $lon2, $mode = true)
    {
        // 緯度経度をラジアンに変換
        $radLat1 = deg2rad($lat1); // 緯度１
        $radLon1 = deg2rad($lon1); // 経度１
        $radLat2 = deg2rad($lat2); // 緯度２
        $radLon2 = deg2rad($lon2); // 経度２

        // 緯度差
        $radLatDiff = $radLat1 - $radLat2;

        // 経度差算
        $radLonDiff = $radLon1 - $radLon2;

        // 平均緯度
        $radLatAve = ($radLat1 + $radLat2) / 2.0;

        // 測地系による値の違い
        $a = $mode ? 6378137.0 : 6377397.155; // 赤道半径
        $b = $mode ? 6356752.314140356 : 6356078.963; // 極半径
        //$e2 = ($a*$a - $b*$b) / ($a*$a);
        $e2 = $mode ? 0.00669438002301188 : 0.00667436061028297; // 第一離心率^2
        //$a1e2 = $a * (1 - $e2);
        $a1e2 = $mode ? 6335439.32708317 : 6334832.10663254; // 赤道上の子午線曲率半径

        $sinLat = sin($radLatAve);
        $W2 = 1.0 - $e2 * ($sinLat*$sinLat);
        $M = $a1e2 / (sqrt($W2)*$W2); // 子午線曲率半径M
        $N = $a / sqrt($W2); // 卯酉線曲率半径

        $t1 = $M * $radLatDiff;
        $t2 = $N * cos($radLatAve) * $radLonDiff;
        $dist = sqrt(($t1*$t1) + ($t2*$t2));

        return $dist;
    }

    /**
     * Check if stock point is existing
     * @param string $stockPointId
     * @return boolean
     */
    public function isExistStockPoint($stockPointId)
    {
        $stockPoint = $this->stockPointFactory->create()->load($stockPointId, 'external_stock_point_id');
        if ($stockPoint->getId()) {
            return $stockPoint;
        }
        return false;
    }

    /**
     * Get profile bucket id by stock point id
     *
     * @param int $stockPointId
     * @return bool|\Riki\StockPoint\Model\StockPointProfileBucket
     */
    public function getBucketIdByStockPointId($stockPointId)
    {
        $bucketModel = $this->stockPointProfileBucketFactory->create()
            ->load((int)$stockPointId, 'stock_point_id');
        if ($bucketModel->getProfileBucketId() && $bucketModel->getStockPointId()) {
            $profileBucketId = $bucketModel->getProfileBucketId();
            return $profileBucketId;
        }

        return false;
    }

    /**
     * Send email notify change to consumer when assign profile to stock point success.
     *
     * @param $emailReceiver
     * @param $emailTemplateVariables
     *
     * @return boolean
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMailNotify($emailReceiver, $emailTemplateVariables = [])
    {
        if ($this->getEmailEnable()) {
            try {
                $this->inlineTranslate->suspend();
                $templateId = $this->getEmailTemplate();
                $senderInfo = $this->getSenderEmail();
                $this->transportBuilder
                    ->setTemplateIdentifier($templateId)
                    ->setTemplateOptions(
                        [
                            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                            'store' => $this->storeManager->getStore()->getId(),
                        ]
                    )
                    ->setTemplateVars($emailTemplateVariables)
                    ->setFrom($senderInfo)
                    ->addTo($emailReceiver);

                $transport = $this->transportBuilder->getTransport();
                $transport->sendMessage();
                $this->inlineTranslate->resume();
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\MailException(
                    __('Error %1', $e->getMessage())
                );
            }
        }

        return true;
    }


    /**
     * Get customer by id
     *
     * @param int $customerId
     *
     * @return Object
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function getCustomerById($customerId)
    {
        try {
            return $this->customerRepository->getById($customerId);
        } catch (\Exception $e){
            throw new \Magento\Framework\Exception\NotFoundException(
                __('Error %1', $e->getMessage())
            );
        }
    }

    /**
     * Get config stock point proxy enable
     *
     * @return mixed
     */
    public function getStockPointProxyEnable()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_STOCK_POINT_PROXY_ENABLE,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get stock point proxy host name
     *
     * @return mixed
     */
    public function getStockPointProxyHostName()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_STOCK_POINT_PROXY_HOST_NAME,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get stock point proxy port
     *
     * @return mixed
     */
    public function getStockPointProxyPort()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_STOCK_POINT_PROXY_PORT,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get stock point proxy username
     *
     * @return mixed
     */
    public function getStockPointProxyUsername()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_STOCK_POINT_PROXY_USERNAME,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * Get stock point proxy password
     *
     * @return mixed
     */
    public function getStockPointProxyPassword()
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_PATH_STOCK_POINT_PROXY_PASSWORD,
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
    }
}
