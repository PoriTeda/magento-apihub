<?php

namespace Riki\Subscription\Helper\Profile;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\FileSystemException;
use Riki\Subscription\Model\Constant;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class CampaignHelper
 * @package Riki\Subscription\Helper\Profile
 */
class CampaignHelper extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * Deployment configuration.
     *
     * @var DeploymentConfig
     */
    protected $deploymentConfig;

    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $simulator;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Riki\CreateProductAttributes\Model\Product\CaseDisplay
     */
    protected $caseDisplay;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfileData;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Riki\Subscription\Model\Multiple\Category\CampaignFactory
     */
    protected $campaignFactory;

    /**
     * @var \Riki\Subscription\Logger\Logger
     */
    protected $logger;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var array
     */
    protected $courseData = [];

    /**
     * @var array
     */
    protected $campaignData = [];

    /**
     * @var \Magento\Framework\Filesystem\File\ReadFactory
     */
    protected $fileReadFactory;


    const PATH_PRIVATE_KEY = 'shop_site/private_key';

    const PATH_PUBLIC_KEY = 'shop_site/public_key';

    const CONSUMER_DB_ID = 'consumer_db_id';

    const PRODUCTS = 'products';

    const CUSTOMER_ID = 'customerId';

    const LANDING_PAGE_ID = 'landing_page_id';

    const TIME_OUT = 600;

    const ENCODE_SIGNATURE_ALG = 'sha256WithRSAEncryption';

    const SUMMER_CAMPAIGN = 'summer_campaign';

    const REQUIRE_DATA_VALUE = 'reqDataValue';

    const SUMMER_CAMPAIGN_DATA = 'summer_campaign_data';

    const SUMMER_CAMPAIGN_CACHE_ID = 'summer_campaign_cache_id';

    const SUCCESS_DATA = 'success_data';

    const PROFILE = 'profile';
    private $privateKey;
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
     * CampaignHelper constructor.
     * @param SerializerInterface $serializer
     * @param DeploymentConfig $deploymentConfig
     * @param \Riki\Subscription\Helper\Order\Simulator $simulator
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Riki\CreateProductAttributes\Model\Product\CaseDisplay $caseDisplay
     * @param Data $helperProfileData
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Riki\Subscription\Model\Multiple\Category\CampaignFactory $campaignFactory
     * @param \Riki\Subscription\Logger\Logger $logger
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        DeploymentConfig $deploymentConfig,
        SerializerInterface $serializer,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Riki\CreateProductAttributes\Model\Product\CaseDisplay $caseDisplay,
        \Riki\Subscription\Helper\Profile\Data $helperProfileData,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Model\Multiple\Category\CampaignFactory $campaignFactory,
        \Riki\Subscription\Logger\Logger $logger,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Framework\Filesystem\File\ReadFactory $fileReadFactory,
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->simulator = $simulator;
        $this->productRepository = $productRepository;
        $this->caseDisplay = $caseDisplay;
        $this->helperProfileData = $helperProfileData;
        $this->courseFactory = $courseFactory;
        $this->campaignFactory = $campaignFactory;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->deploymentConfig = $deploymentConfig;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->productCollectionFactory = $productCollectionFactory;
        parent::__construct($context);
        $this->fileReadFactory = $fileReadFactory;
    }

    /**
     * Check is exist summer page id
     *
     * @param $summerPageId
     *
     * @return bool
     */
    public function isExistSummerPageId($summerPageId)
    {
        if (is_numeric($summerPageId)) {
            return true;
        }
        return false;
    }

    /**
     * Load campaign factory
     *
     * @param int $campaignId
     * @return mixed
     */
    public function loadCampaign($campaignId)
    {
        if (isset($this->campaignData[$campaignId])) {
            return $this->campaignData[$campaignId];
        }
        $campaignModel = $this->campaignFactory->create()->load($campaignId);
        $this->campaignData[$campaignId] = $campaignModel;
        return $this->campaignData[$campaignId];
    }

    /**
     * Simulator with object data
     *
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @param array $arrNewProducts
     * @return object|bool
     */
    public function simulator($profile, $arrNewProducts)
    {
        $objectData = $this->prepareSimulator($profile, $arrNewProducts);
        if ($objectData) {
            return $this->simulator->createSimulatorOrderHasData($objectData);
        }
        return false;
    }

    /**
     * Prepare data before simulate
     *
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @param array $arrNewProducts
     * @return bool|DataObject
     */
    public function prepareSimulator($profile, $arrNewProducts)
    {
        if ($profile instanceof \Riki\Subscription\Model\Profile\Profile) {
            $arrNewProductData = [];

            foreach ($arrNewProducts as $newSpotProduct) {
                /** @var \Magento\Catalog\Model\Product $objectNewProduct */
                $objectNewProduct = $newSpotProduct['product'];
                $productQty = $newSpotProduct['qty_request'];
                $pieceQty = $this->caseDisplay->getQtyPieceCaseForSaving(
                    $objectNewProduct->getData('case_display'),
                    $objectNewProduct->getData('unit_qty'),
                    $productQty
                );
                $caseDisplay = $this->getCaseDisplayKey($objectNewProduct->getData('case_display'));
                $unit = $this->validateQtyPieceCase(
                    $objectNewProduct->getData('case_display'),
                    $objectNewProduct->getData('unit_qty')
                );

                $data = [
                    'product_id' => $objectNewProduct->getId(),
                    'qty' => $pieceQty,
                    'product_type' => $objectNewProduct->getTypeId(),
                    'product_options' => '',
                    'unit_case' => $caseDisplay,
                    'unit_qty' => $unit,
                    'gw_id' => '',
                    'gift_message_id' => ''
                ];

                $arrNewProductData[] = $data;
            }

            $productCartData = $this->helperProfileData->makeProductCartData($profile->getId(), [], $arrNewProductData);
            $courseModel = $this->loadCourse($profile->getData('course_id'));

            $obj = new DataObject();
            $obj->setData($profile->getData());
            $obj->setData('course_data', $courseModel);
            $obj->setData("product_cart", $productCartData);
            return $obj;
        }

        return false;
    }

    /**
     * Merge product with same product id
     *
     * @param array $data
     * @return mixed
     */
    public function mergeProductWithSameId($data, $type = null)
    {
        $arrResult = [];
        if ($type == self::SUMMER_CAMPAIGN) {
            $arrDataProduct = $data['products'];
            foreach ($arrDataProduct as $key => $item) {
                if ($item['sku']) {
                    $product = $this->productRepository->get($item['sku']);
                    $arrDataProduct[$key]['product_id'] = $product ? $product->getId() : 0;
                } else {
                    $arrDataProduct[$key]['product_id'] = 0;
                }
            }
        } else {
            $arrDataProduct = $data['data']['product'];
        }

        foreach ($arrDataProduct as $item) {
            if (!isset($item['qty_case'])) {
                $item['qty_case'] = 0;
            }

            if (!isset($item['qty'])) {
                $item['qty'] = 0;
            }

            if ($this->checkProductExitsInArr($arrResult, $item['product_id'])) {
                $arrResult[$item['product_id']]['qty'] += $item['qty'];
                $arrResult[$item['product_id']]['qty_case'] += $item['qty_case'];
            } else {
                if ($item['qty'] || $item['qty_case']) {
                    $arrResult[$item['product_id']] = $item;
                }
            }
        }

        return $arrResult;
    }

    /**
     * Check product exists in array
     *
     * @param array $arrResult
     * @param int $productId
     * @return mixed
     */
    public function checkProductExitsInArr($arrResult, $productId)
    {
        $arrAllKey = array_keys($arrResult);
        if (in_array($productId, $arrAllKey)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Initialize product instance from request data
     *
     * @param int $id
     * @return \Magento\Catalog\Model\Product|false
     */
    public function initProductWithId($id)
    {
        $productId = (int)$id;
        if ($productId) {
            try {
                return $this->productRepository->getById($productId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Get case display
     *
     * @param $case
     * @return string
     */
    public function getCaseDisplayKey($case)
    {
        return $this->caseDisplay->getCaseDisplayKey($case);
    }

    /**
     * Validate qty piece case
     *
     * @param $display
     * @param $unitQty
     * @return int
     */
    public function validateQtyPieceCase($display, $unitQty)
    {
        return $this->caseDisplay->validateQtyPieceCase($display, $unitQty);
    }

    /**
     * Load course factory
     *
     * @param $courseId
     * @return mixed
     */
    public function loadCourse($courseId)
    {
        if (isset($this->courseData[$courseId])) {
            return $this->courseData[$courseId];
        }
        $courseModel = $this->courseFactory->create()->load($courseId);
        $this->courseData[$courseId] = $courseModel;
        return $this->courseData[$courseId];
    }

    /**
     * Is profile stock point
     *
     * @param $profileData
     * @return null|string
     */
    public function isProfileStockPoint($profileData)
    {
        if ($profileData) {
            if ($profileData->getData('stock_point_profile_bucket_id')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate product added to stock point
     *
     * @param $profile
     * @param $product
     * @param $productQty
     * @param $isSave
     * @return $this|bool
     */
    public function validateProductAddedToStockPoint($profile, $product, $productQty, $isSave = false)
    {
        if (!$productQty) {
            $productQty = 1;
        }

        if (!$isSave) {
            $unitQty = $product->getData('unit_qty');
            $unitDisplay = $product->getData('case_display');
            $productQty = $this->caseDisplay->getQtyPieceCaseForSaving($unitDisplay, $unitQty, $productQty);
        }
        $productId = $product->getId();

        $arrProduct = [
            $productId => [
                'product' => $product,
                'qty' => $productQty
            ]
        ];

        $isAllow = $this->validateStockPointProduct->checkProductAllowStockPoint($profile, $product, $arrProduct);
        return $isAllow;
    }

    /**
     * Get product collection by ids
     *
     * @param $productIds
     * @return null|string
     */
    public function getProductCollectionByIds($productIds)
    {
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $collection = $this->productCollectionFactory->create();
        $collection->addFieldToFilter('entity_id', ['in' => $productIds]);
        $collection->addAttributeToSelect('*');

        return $collection;
    }

    /**
     * Validate spot product is exits in product cart
     *
     * @param $profile
     * @param $productId
     * @return int
     */
    public function validateSpotProductIsExistInProfile($profile, $productId)
    {
        $result = 0;
        /* @var \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart\Collection $profileProductCart */
        $profileProductCarts = $profile->getProductCart();

        foreach ($profileProductCarts as $productCart) {
            if ($productCart->getData('product_id') == $productId) {
                if ($productCart->getData('is_spot') == 1) {
                    $result = Constant::ADD_SPOT_PRODUCT_ERROR_SPORT_PRODUCT_IS_EXIST_LIKE_SPOT;
                } else {
                    $result = Constant::ADD_SPOT_PRODUCT_ERROR_SPOT_PRODUCT_IS_EXIST_LIKE_MAIN_PRODUCT;
                }
            }
        }

        return $result;
    }

    /**
     * @param $receiveData
     *
     * @return array
     */
    public function validatePostAuthorization($receiveData)
    {

        $result = [
            'isValid'  => false,
            'errorMsg' => ''
        ];

        if (empty($receiveData) || !isset($receiveData['data']) || !isset($receiveData['sig'])) {
            $result['errorMsg'] = __('Request Data is missing');
            return $result;
        }

        $rawData = $this->decodeData($receiveData['data']);

        if (!isset($rawData['sectime'])) {
            $result['errorMsg'] = __('Sectime is missing');
            return $result;
        }

        if (time() - strtotime($rawData['sectime']) > self::TIME_OUT) {
            $result['errorMsg'] = __('The request has expired');
            return $result;
        }

        try {
            $verifyResult = openssl_verify($receiveData['data'], base64_decode($receiveData['sig']), $this->getPublicKey(), self::ENCODE_SIGNATURE_ALG);
        } catch (\Exception $e) {
            $result['errorMsg'] = $e->getMessage();
            return $result;
        }

        if ($verifyResult != 1) {
            $result['errorMsg'] = __('Invalid signature');
            return $result;
        }

        if (!isset($rawData['consumer_db_id'])) {
            $result['errorMsg'] = __('There are something wrong via the system. Please contact our call center for helping.');
            return $result;
        }

        if (empty($rawData['products'])) {
            $result['errorMsg'] = __('There are no products to process.');
            return $result;
        }

        if (empty($rawData['landing_page_id'])) {
            $result['errorMsg'] = __('Landing page id is required.');
            return $result;
        }
        $result['isValid'] = true;

        return $result;

    }

    /**
     * @return mixed
     * @throws FileSystemException
     */
    public function getPublicKey()
    {
        $publicKeyPath = $this->deploymentConfig->get(self::PATH_PUBLIC_KEY);
        return $this->getDataFile($publicKeyPath);
    }

    /**
     * @param $data
     *
     * @return array|bool|float|int|string|null
     */
    public function decodeData($data)
    {
        $receiveJsonData = base64_decode($data);
        return $this->serializer->unserialize($receiveJsonData);
    }

    /**
     * @param array $rawDataValue
     * @throws FileSystemException
     */
    public function setPostDataRequest($rawDataValue)
    {
        $rawDataValue['sectime'] = date(DATE_ISO8601);
        $this->buildDataWithOpenSSL($rawDataValue);
    }

    /**
     * @param array $rawDataValue
     *
     * @return string
     * @throws FileSystemException
     */
    private function buildDataWithOpenSSL($rawDataValue)
    {
        /**
         * Get private key
         */
        $dataPrivateKey = $this->getPrivateKey();

        /**
         * Build {{DATA_VALUE}}
         */
        $rawDataValue       = json_encode($rawDataValue);
        $this->b64DataValue = base64_encode($rawDataValue);
        $privateKeyId       = openssl_pkey_get_private($dataPrivateKey);
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
        $this->b64SigValue     = base64_encode($rawSigValue);
        $rawReqDataValue       = [
            'data' => $this->b64DataValue,
            'sig'  => $this->b64SigValue
        ];
        $rawReqDataValue       = json_encode($rawReqDataValue);
        $this->rawReqDataValue = base64_encode($rawReqDataValue);
        return $this->rawReqDataValue;
    }

    /**
     * @return string
     */
    public function getPostDataRequestGenerate()
    {
        return $this->rawReqDataValue;
    }

    /**
     * @return mixed
     */
    private function getPrivateKey()
    {
        $path = $this->deploymentConfig->get(self::PATH_PRIVATE_KEY);
        if ($path != null) {
            $this->privateKey = $this->getDataFile($path);
        }
        return $this->privateKey;
    }

    /**
     * Get data value
     *
     * @param $path
     *
     * @return mixed
     */
    public function getDataFile($path)
    {
        $read = $this->fileReadFactory->create($path, \Magento\Framework\Filesystem\DriverPool::FILE);
        return $read->readAll();
    }
}
