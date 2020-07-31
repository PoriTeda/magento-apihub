<?php

namespace Nestle\Gillette\Model;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Helper\ImageFactory as ProductImageHelper;
use Magento\Store\Model\StoreManagerInterface as StoreManager;
use Magento\Store\Model\App\Emulation as AppEmulation;
use Magento\Quote\Api\Data\CartItemExtensionFactory;

class ProductInfo
    implements \Nestle\Gillette\Api\ProductInfoInterface
{
    const GILLETTE_COURSE_CONFIG_PATH = "gillette/general/coursecode";
    const GILLETTE_PRODUCT_SKU = "gillette/general/gillette_sku";
    const GILLETTE_BLADE_SKU = "gillette/general/blade_sku";
    const GILLETTE_CART_RULES_CAN_APPLY = 'gillette/general/cart_rules_can_apply';
    const FREQUENCY_ID = "frequency_id";
    const CONSUMERDB_ID = "consumerdb_id";
    const COURSE_CODE = "course_code";
    const ALIAS = 'GILLETTE';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;
    
    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepo
     */ 
    public $courseRepo;
    
    /**
     * @var \Psr\Log\LoggerInterface $logger
     */
    public $debuglogger;
    
    /**
     * @var \Riki\SubscriptionCourse\Helper\Data $courseHelper
     */
    public $courseHelper;
     
    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;
    
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry $coreRegistry
     */
    protected $coreRegistry = null;
    
    /**
     * @var \Riki\SubscriptionPage\Model\PriceBox $priceBox
     */
    protected $priceBox;
    
    /**
     * @var \Magento\Store\Model\App\Emulation $appEmulation
     */
    protected $appEmulation;
    
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection
     */
    protected $productCollection;

    /**
     * @var \Magento\Catalog\Helper\ImageFactory $productImageHelper
     */
    protected $productImageHelper;
    
    /**
     *  @var \Riki\Catalog\Helper\Data $catalogHelper
     */ 
    protected $catalogHelper;
    
    /**
     * @var \Magento\GiftWrapping\Helper\Data $giftWrappingData
     */
    protected $giftWrappingData;
    
    /** 
     * @var \Magento\Tax\Model\TaxCalculation $taxCalculation
     */
    protected $taxCalculation;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $cache;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Serialize
     */
    private $serializer;
    
    /**
     *  @var \Riki\SubscriptionCourse\Model\Course $_privateCourseModel
     */
    private $_privateCourseModel = null; 
    
    /**
     * @var \Magento\Catalog\Model\Product $_gilletteProductModel
     */ 
    private $_gilletteProductModel = null;
    
    /**
     *  @var \Magento\Catalog\Model\Product $_bladeProductModel
     */ 
    private $_bladeProductModel = null;
    
    /**
     *  List of execluding product attributes
     *  @var array $_excludingProductAttributes
     */ 
    private $_excludingProductAttributes = 
                ['category_ids',
                 'category_links',
                 'product_links',
                 'attribute_set_id',
                 'price',
                 'gift_wrapping',
                 'visibility',
                 'type_id',
                 'weight',
                 'product_links',
                 'options',
                 'media_gallery_entries',
                 'image',
                 'swatch_image',
                 'small_image',
                 'url_key',
                 'material_type',
                 'thumbnail',
                 'backfront_visibility',
                 'msrp_display_actual_price_type',
                 'point_currency',
                 'allow_seasonal_skip',
                 'seasonal_skip_optional',
                 'ph_code',
                 'filter_part_applicable',
                 'chirashi',
                 'priority',
                 'website_ids',
                 'is_returnable'
                ];
    
    /**
     *  List of execluding course attribute
     *  @var array $_excludingProductAttributes
     */ 
    private $_excludingCourseAttributes = 
                ['must_select_sku',
                 'subscription_type',
                 'hanpukai_type',
                 'hanpukai_maximum_order_times',
                 'hanpukai_delivery_date_allowed',
                 'hanpukai_delivery_date_from',
                 'hanpukai_delivery_date_to',
                 'hanpukai_first_delivery_date',
                 'navigation_path',
                 'design',
                 'additional_category_description',
                 'point_for_trial',
                 'point_for_trial_wbs',
                 'point_for_trial_account_code',
                 'nth_delivery_simulation',
                 'is_delay_payment',
                 'is_shopping_point_deduction',
                 'payment_delay_time',
                 'next_delivery_date_calculation_option',
                 'website_ids',
                 'category_ids',
                 'profile_category_ids',
                 'additional_category_ids',
                 'membership_ids',
                 'merge_profile_to',
                 'multi_machine'
                ];

    protected $identifier;
    
    public function __construct(
    \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
    \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter, 
    \Magento\Framework\Registry $coreRegistry,
    \Magento\Store\Model\App\Emulation $appEmulation,
    \Magento\Catalog\Helper\ImageFactory $productImageHelper,
    \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
    \Magento\Catalog\Model\ResourceModel\Product\Collection $productCollection,
    \Magento\GiftWrapping\Helper\Data $giftWrappingData,
    \Magento\Tax\Model\TaxCalculation $taxCalculation,
    \Riki\Catalog\Helper\Data $catalogHelper, 
    \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepo,
    \Riki\SubscriptionCourse\Helper\Data $courseHelper,
    \Riki\SubscriptionPage\Model\PriceBox $priceBox,
    \Psr\Log\LoggerInterface $debuglogger,
    \Magento\Framework\App\CacheInterface $cache,
    \Magento\Framework\Serialize\Serializer\Serialize $serialize
    ){
        $this->scopeConfig = $scopeConfig;
        $this->courseRepo = $courseRepo;
        $this->debuglogger = $debuglogger;
        $this->courseHelper = $courseHelper;
        $this->appEmulation = $appEmulation;
        $this->productImageHelper = $productImageHelper;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->priceBox = $priceBox;
        $this->giftWrappingData = $giftWrappingData;
        $this->taxCalculation = $taxCalculation;
        $this->coreRegistry = $coreRegistry;
        $this->catalogHelper = $catalogHelper;
        $this->productRepository = $productRepository;
        $this->productCollection = $productCollection;
        $this->cache = $cache;
        $this->serializer  = $serialize;
    }
    /**
     * This function used to get the customer information from consumer DB ID
     * @return 
     */ 
    protected function getContextCustomerInfo(){
        $consumerDbId = $this->coreRegistry->registry(self::CONSUMERDB_ID);
        return \Zend_Validate::is($consumerDbId,'NotEmpty');
        // TOOD: to be add later
    }
    
    /**
     *  This function is used for getting context customer group 
     *  in order to determine the catalogrule price
     *  @return int $customerGroupId
     */ 
    protected function getCustomerGroup(){
        // TODO: to be updated later for getting real customer group
        // 0: No logged in customer group
        // 1: EC customer
        return $this->getContextCustomerInfo()?0:1;
    }
    
    /**
     * @param $product
     * @return string
     */
    public function getProductPrice($product) {
        if ($product->getTypeId() != 'bundle') {
            $finalPrice = $product->getPriceInfo()->getPrice(\Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE);
            $finalPrice = $finalPrice->getAmount()->getValue() ?: 0;
            return intval($finalPrice);
        } else {
            $price = intval($product->getPriceInfo()->getPrice('final_price')->getValue());
            return $price ;
        }
    }
    
    /**
     * Helper function that provides full cache image url
     * @param \Magento\Catalog\Model\Product
     * @param string|null $imageType
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getImageUrl($product, string $imageType = null){
        $storeId = 1; // This API only support for EC store
        $this->appEmulation->startEnvironmentEmulation($storeId, \Magento\Framework\App\Area::AREA_FRONTEND, true);
        $imageUrl = $this->productImageHelper->create()->init($product, $imageType)->getUrl();
        $this->appEmulation->stopEnvironmentEmulation();
        return $imageUrl;
    }

    /**
     * @param $configName
     * @return mixed
     */
    public function getStockConfigByPath($configName)
    {
        return $this->scopeConfig->getValue('cataloginventory/item_options/' . $configName);
    }

    /**
     * This function is used for checking if product is avaiable for showing on the frontend or not
     * @params \Magento\Catalog\Model\Product $product 
     * @params boolean $isHanpukai
     * @return boolean
     */ 
    protected function checkProductAvailableForShow($product, $isHanpukai = false)
    {
        /** @var \Magento\Catalog\Model\Product */
        $storeIdsOfProduct = $product->getStoreIds();
        $currentStoreId = 1; // always EC site
        $productIsActiveInStore = false;
        if (in_array($currentStoreId, $storeIdsOfProduct)) {
            $productIsActiveInStore = true;
        }
        if ($isHanpukai == true && !$product->getIsSalable()) {
            self::$haveProductOutOfStockInHanpukaiCourse = true;
        }

        if ($product->getStatus() == 1
            && $product->getVisibility() != \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE
            && $productIsActiveInStore
        ) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * This function is used to calculate the final price for gift wrapping fee
     * @param $wrappingFee
     * @return mixed
     */
    public function calTax($wrappingFee)
    {
        $wrappingTax = $this->giftWrappingData->getWrappingTaxClass(1);
        $wrappingRate = $this->taxCalculation->getCalculatedRate($wrappingTax);
        if ($wrappingFee > 0) {
            $taxRate = $wrappingRate/100;
            $wrappingFee = $wrappingFee + ($taxRate*$wrappingFee);
        }
        return (int)$wrappingFee ;
    }
    
    /**
     * this function to be used privately by this model due to business constraints
     * DO NOT RE-USE this function in anywhere
     * @params string $identity
     * @params boolean $loadBySku
     * @return array
     * @throws NoSuchEntityException
     */ 
    protected function phaseProductData($identity, $loadBySku = false){
        
        $output = [];
        // if the input param is an integer, that's a product ID
        if(!$loadBySku){
            goto getById;
        } else {
            goto getBySku;
        }
        /** @var \Magento\Catalog\Model\Product $product */
        $product = null;
        getBySku: {
            try{
                $product = $this->productRepository->get($identity,false,1,true);
            }
            catch (NoSuchEntityException $exception){ // handle not found exception
                $this->debuglogger->warning("Gillette_API debug: SKU {$identity} is not found",['exception' => $exception]);
                return $output;
            }
            catch(\Exception $exception){ // global exception case, do not handle
                throw $exception;
            }
            // check if product is avaiable or not
            if($this->checkProductAvailableForShow($product)){
                goto phaseAsArray;
            } else {
                goto end;
            }

        }
        
        getById: {
            try{
                $product = $this->productRepository->getById($identity,false,1,true);
            }
            catch (NoSuchEntityException $exception){ // handle not found exception
                $this->debuglogger->warning("Gillette_API debug: ID {$identity} is not found",['exception' => $exception]);
                return $output;
            }
            catch(\Exception $exception){ // global exception case, do not handle
                throw $exception;
            }
            // check if product is avaiable or not
            if($this->checkProductAvailableForShow($product)){
                goto phaseAsArray;
            } else {
                goto end;
            }
        }

        phaseAsArray: {
            $product->setQty(1)
                    ->setCustomerGroupId($this->getCustomerGroup());
            $fullPrice = $this->priceBox->getFinalProductPrice($product);
            /** @var array $availableGiftWrapping */
            $availableGiftWrapping = array_map(function($gwItem){
                return [
                   "gw_value" => $gwItem->getId(),
                   "gw_label" => $gwItem->getGiftName(),
                   "gw_code"  => $gwItem->getGiftCode(),
                   "price"    => $this->calTax($gwItem->getBasePrice())
                ];
            },$this->catalogHelper->getGiftWrappingByProduct($product));
            $mediaGallery = array_map(function($item){
                if(isset($item["url"]) && 
                  !empty($item["url"]) && 
                  $item["disabled"] != 1 && 
                  $item["media_type"] == "image"){
                    return [
                       "label" => $item["label"], 
                       "url"   => $item["url"]
                    ];
                } else {
                    return null;
                }
            },$product->getMediaGalleryImages()->toArray()["items"]);
            $output = array_diff_key(array_merge($this->extensibleDataObjectConverter->toNestedArray(
                    $product,
                    [],
                    ProductInterface::class
                    ),[
                        "gallery"      => $mediaGallery, 
                        "available_gw" => $availableGiftWrapping,
                        "final_price" => (int)$fullPrice[0],
                        "frontend_image_url" => $this->getImageUrl($product,'product_thumbnail_image')
                    ]), array_flip($this->_excludingProductAttributes));
        }

        end: {
            return $output;
        }
    }
    
    /**
     * This function to be used to get the course model
     * @param s string $courseCode
     * @return \Riki\SubscriptionCourse\Model\Course
     * @throws NoSuchEntityException
     */ 
    protected function getSelectedCourseCode($courseCode){
        
        if(\Zend_Validate::is($this->_privateCourseModel,'NotEmpty')){
            return $this->_privateCourseModel;
        }
        
        if(!\Zend_Validate::is($courseCode,'NotEmpty')){
            throw new NoSuchEntityException(__("Input course code is empty"));
        }
        try{
            $course = $this->courseRepo->getCourseByCode($courseCode);
            if ($course && $course->getIsEnable()) {
                return $course;
            }
        }
        catch (NoSuchEntityException $exception){ // handle not found exception
            $this->debuglogger->debug(printf("Gillette_API debug: Configured course code %s is not found", $courseCode));
            throw $exception;
        }
        catch(\Exception $exception){ // global exception case, do not handle
            throw $exception;
        }
        
        throw new NoSuchEntityException(
            __(
                'No course with %fieldName = %fieldValue',
                ['fieldName' => 'course_code', 'fieldValue' => $courseCode]
            )
        );
    }



    /**
     * append model course data into the output array
     * @input array $output
     * @throws NoSuchEntityException
     * @return void
     */ 
    protected function appendCourseInfo(&$output){
        $courseCode = $this->coreRegistry->registry(self::COURSE_CODE);
        /** @var \Riki\SubscriptionCourse\Model\Course $courseModel */
        $courseModel = $this->getSelectedCourseCode($courseCode);
        $output = array_merge($output,$courseModel->getData());
        
        // remove unneccessary item
        if(isset($output["frequency_ids"]) && !empty($output["frequency_ids"])){
            // modifying id
            $allFrequencies = $courseModel->getFrequencyValuesForForm();
            $selectedFrequencies = array();
            foreach($allFrequencies as $value => $entity){
                if(in_array($entity["value"],$output["frequency_ids"])){
                    $selectedFrequencies[] = $entity;
                }
            }
            $output["frequencies"] = $selectedFrequencies;
            unset($output["frequency_ids"]);
        }
        
        if(isset($output["payment_ids"]) && !empty($output["payment_ids"])){
            unset($output["payment_ids"]);
        }
        
        // load machine of subscription course
        if(isset($output["machines"]) && !empty($output["machines"])){
            unset($output["machines"]);
        }
            
        $machines = [];
        try {
            $sortedMachines = $courseModel->getProductMachines();
             if(!\Zend_Validate::is($sortedMachines,'NotEmpty')){
                $output["machines"] = $machines;
                return true;
            }
            $machines = array_map(function ($machine) {
                return $this->phaseProductData($machine['product_id']);
            }, $sortedMachines);

        }
        catch(NoSuchEntityException $exception){
            $this->debuglogger->debug("Gillette_API debug: Course$courseCodes does not have any machine");
            $machines = []; 
        }
        catch(\Exception $exception){ // global exception case
            throw $exception; // Not handing this case of exception
        }
        $output["machines"] = $machines;
        $output = array_diff_key($output,  
            array_flip($this->_excludingCourseAttributes)
        ); 
        return true;
    }
    
    /**
     * This function used to append gillette product info
     * @input array $output
     * @throws NoSuchEntityException
     * @return void
     */
    protected function appendSpotProductInfo(&$output){
        $gilletteSku = $this->scopeConfig->getValue(
               self::GILLETTE_PRODUCT_SKU
        );
        
        $bladeSku = $this->scopeConfig->getValue(
               self::GILLETTE_BLADE_SKU
        );
        if(isset($output["gillette_product"]) && !empty($output["gillette_product"])){
            unset($output["gillette_product"]);
        }
        $output["gillette_product"] = $this->phaseProductData($gilletteSku, true);

        if(isset($output["blade_product"]) && !empty($output["blade_product"])){
            unset($output["blade_product"]);
        }
        $output["blade_product"] = $this->phaseProductData($bladeSku, true);
        return true;
    }

    /**
     * This function used to append dolce gusto capsule product info
     * @input array $output
     * @throws NoSuchEntityException
     * @return void
     */
    protected function appendCapsuleProductInfo(&$output){
        $courseCode = $this->coreRegistry->registry(self::COURSE_CODE);
        /** @var \Riki\SubscriptionCourse\Model\Course $courseModel */
        $courseModel = $this->getSelectedCourseCode($courseCode);
        $arrProductIds =  $this->courseHelper->getAllProductIdOfCourseByObject($courseModel);
        $isHanpukai = false;
        
        if (!\Zend_Validate::is($arrProductIds,'NotEmpty')){
            $output["subscription_products"] = [];
            return ;
        }
        
        $result = array_map(function($productId) {
            return $this->phaseProductData($productId);
        }, $arrProductIds);

        $output["subscription_products"] = array_filter($result, function($element){
            // validate not empty array
            if(!\Zend_Validate::is($element,'NotEmpty')){
                return false;
            }
            $gilletteSku = $this->scopeConfig->getValue(
                   self::GILLETTE_PRODUCT_SKU
            );
            // validate not the blade product
            $bladeSku = $this->scopeConfig->getValue(
                   self::GILLETTE_BLADE_SKU
            );
            return !in_array($element["sku"], [$bladeSku, $gilletteSku]);
        });
        return;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getProducts(
        $courseCode,        // posted subscription course code
        $consumerDbId,     // posted consumer DB ID
        $selectedFrequency // posted frequency id
    ){
        $this->identifier = self::ALIAS
            . '_' . $courseCode
            . '_' . (boolean)$consumerDbId
            . '_' . $selectedFrequency;
        if($data = $this->getCache()) {
            return $data;
        }
        // prepare the outout data in advance
        $output = array();
        
        
        // logger for debug API
        $this->debuglogger->info("Gillette_API debug: Received {$consumerDbId} consumer DB ID");
        $this->debuglogger->info("Gillette_API debug: Received {$selectedFrequency} frequency");

        $this->coreRegistry->register(self::CONSUMERDB_ID, $consumerDbId);

        if(!\Zend_Validate::is($courseCode,'NotEmpty')){
            goto constrollerSPOT;
        } else {
            goto constrollerSUB;
        }
        
        constrollerSPOT: {
            $this->appendSpotProductInfo($output);
            goto end;
        }
        
        constrollerSUB: {

            try{
            /** @var \Riki\SubscriptionCourse\Model\Course $courseModel */
                $courseModel = $this->getSelectedCourseCode($courseCode);
            }
            catch(Exception $e){ // do not handle global exception
                throw $e;
            }
            catch(NoSuchEntityException $e){ // do not handle
                throw $e;
            }
            
            if(!\Zend_Validate::is($selectedFrequency,'NotEmpty')){
                $selectedFrequency = 21; // 1-month
            }
            
            $this->coreRegistry->register(self::COURSE_CODE, $courseCode);
            $this->coreRegistry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $selectedFrequency);
            $this->coreRegistry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseModel->getId());

            // appending course data
            $this->appendCourseInfo($output);
            $this->appendSpotProductInfo($output);
            $this->appendCapsuleProductInfo($output);

            goto end;
        }

        end: {
            $this->setCache([$output]);
            return [$output];
        }

    }

    public function setCache($data) {
        $this->cache->save(
            $this->serializer->serialize($data),
            $this->identifier,
            [\Magento\Framework\App\Cache\Type\Block::TYPE_IDENTIFIER],
            86400
        );
    }
    public function getCache() {
        $cache = $this->cache->load($this->identifier);
        try {
            if ($cache) {
                $data = $this->serializer->unserialize($cache);
                return is_array($data)? $data : false;
            }
        } catch (\Exception $e) {
            return false;
        }
        return false;
    }
}
