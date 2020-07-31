<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Catalog\Model;

use Magento\Catalog\Api\Data\CategoryProductLinkExtension;
use Magento\Catalog\Api\Data\CategoryProductLinkExtensionFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryProductLinkInterfaceFactory;
use Magento\Catalog\Model\Product\Visibility;
use Riki\Subscription\Model\ProductCart\ProductCart;
use Riki\SubscriptionCourse\Model\ResourceModel\Course;
use Riki\Subscription\Model\Profile\Profile;
use Riki\CreateProductAttributes\Model\Product\UnitSap;
use Magento\Framework\Pricing\Adjustment\CalculatorInterface;
use Riki\Subscription\Helper\Data as SubscriptionHelperData;
use Magento\Framework\Pricing\Helper\Data as HelperPrice;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Webapi\Rest\Request;
use Magento\CatalogInventory\Model\Spi\StockRegistryProviderInterface;

class CategoryLinkManagement implements \Riki\Catalog\Api\CategoryLinkManagementInterface
{

    /**
     * @var StockRegistryInterface
     */
    protected $stockStatus;
    /**
     * @var ProductFactory
     */
    protected $_productloader;
    /**
     * @var ProductCart
     */
    protected $_productCart;
    /**
     * @var Course
     */
    protected $resourceCoure;
    /**
     * @var Profile
     */
    protected $subscriptionProfile;
    /**
     * @var CategoryProductLinkInterfaceFactory
     */
    protected $productLinkFactory;
    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;
    /**
     * @var UnitSap
     */
    protected $resourceUnitSap;
    /**
     * @var CalculatorInterface
     */
    protected $_adjustmentCalculator;
    /**
     * @var HelperPrice
     */
    protected $_helperPrice;
    /**
     * @var SubscriptionHelperData
     */
    protected $_subHelperData;
    /**
     * @var PriceCurrencyInterface
     */
    protected $_priceCurrencyInterface;

    protected $_storeId;
    /**
     * @var Request
     */
    protected $request;

    const STATUS_ACTIVE = 1;
    const COMPLETE_SUCCESS_HAS_DATA = '00';
    const COMPLETE_SUCCESS_NOT_DATA = '01';
    const WRONG_INPUT_FORMAT        = '40';
    const MESSAGE_SYSTEM_ERROR      = '99';
    const MESSAGE_ERROR_CATEGORY    = '11';
    const MESSAGE_ERROR_PROFILE_ID  = '12';
    const MESSAGE_ERROR_CATEGORY_PROFILE    = '10';
    const MESSAGE_ERROR_WRONG_INPUT_FORMAT  = '40';

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $productVisibility;
    /**
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    protected $stockHelper;
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * CategoryLinkManagement constructor.
     * @param CategoryProductLinkInterfaceFactory $productLinkFactory
     * @param StockRegistryInterface $stockStatus
     * @param CalculatorInterface $adjustmentCalculator
     * @param SubscriptionHelperData $subscriptionHelperData
     * @param CategoryRepositoryInterface $categoryRepository
     * @param PriceCurrencyInterface $priceCurrencyInterface
     * @param Profile $subscriptionProfile
     * @param Course $resourceCourse
     * @param UnitSap $resourceUnitSap
     */
    public function __construct(
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        CategoryProductLinkInterfaceFactory $productLinkFactory,
        StockRegistryInterface $stockStatus,
        CalculatorInterface $adjustmentCalculator,
        SubscriptionHelperData $subscriptionHelperData,
        CategoryRepositoryInterface $categoryRepository,
        PriceCurrencyInterface $priceCurrencyInterface,
        Profile $subscriptionProfile,
        Course $resourceCourse,
        UnitSap $resourceUnitSap,
        Request $request,
        \Magento\Framework\Registry $registry,
        \Magento\CatalogInventory\Helper\Stock $stockHelper
    )
    {
        $this->resourceUnitSap= $resourceUnitSap;
        $this->stockStatus    = $stockStatus;
        $this->resourceCoure  = $resourceCourse;
        $this->subscriptionProfile   = $subscriptionProfile;
        $this->productLinkFactory    = $productLinkFactory;
        $this->categoryRepository    = $categoryRepository;
        $this->_adjustmentCalculator = $adjustmentCalculator;
        $this->_subHelperData        = $subscriptionHelperData;
        $this->_priceCurrencyInterface  = $priceCurrencyInterface;
        $this->productVisibility = $productVisibility;
        $this->request = $request;
        $this->stockHelper = $stockHelper;
        $this->registry = $registry;
    }

    public function getProductUnit($entityItem){
        $unitCaseOnly  = \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY;
        $unitPieceOnly = \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_PIECE_ONLY;

        $unit       = (int)$entityItem->getData('case_display');
        $unitSap    = $this->resourceUnitSap;
        $arrUnitSap = $unitSap::getOptionArray();

        if ($unit==$unitCaseOnly){
            /* @var \Magento\Framework\Phrase $dataUnitSap  */
            $dataUnitSap   = $arrUnitSap[$unitSap::CS_CASE];
            $textTranslate = $dataUnitSap->getText();
            return __(' '.$textTranslate . ' ');
        }else if ($unit==$unitPieceOnly){
            /* @var \Magento\Framework\Phrase $dataUnitSap  */
            $dataUnitSap   = $arrUnitSap[$unitSap::EA_EACH];
            $textTranslate = $dataUnitSap->getText();
            return __(' '.$textTranslate . ' ');
        }

        return '';
    }

    /**
     * get subscription profile
     *
     * @param $profileId
     * @return $this|null
     */
    public function getSubscriptionProfile($profileId){
        $profile = $this->subscriptionProfile->load($profileId);
        if($profile->getCourseId() !=null){
            return $profile;
        }
        return null;
    }

    /**
     * get price currentcy
     *
     * @param $price
     * @return int|string
     */
    public function formatCurrency($price)
    {
        if($price !=null){
            $price = $this->_priceCurrencyInterface->round($price);
            return number_format($price,4);
        }
        return 0;
    }

    /**
     * get product price
     *
     * @param $product
     * @return float|int|string
     */
    public function getProductPrice($product) {
        $price         = (int)$product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
        return $price;
    }

    /**
     * get product position
     *
     * @param $arrProductPosition
     * @param $productId
     * @return mixed|null
     */
    public function getPositionProductOnCategory($arrProductPosition,$productId){
        if(is_array($arrProductPosition) && count($arrProductPosition)>0){
            if(isset($arrProductPosition[$productId])){
                return $arrProductPosition[$productId];
            }
        }
        return 0;
    }

    /**
     * check product on store
     *
     * @param $product
     *
     * @return bool
     */
    public function checkStoreId($product){
        $storeId           = $this->_storeId;
        $arrStoreId        = $product->getWebsiteIds();
        if(is_array($arrStoreId) && count($arrStoreId)>0 ){
            if(in_array($storeId,$arrStoreId)){
                return true;
            }
        }
        return false;
    }

    /**
     * Get error message
     *
     * @param $code
     * @param null $dataInput
     *
     * @return mixed
     */
    public function getMessageError($code,$dataInput=null)
    {
        $arrMessage['ReturnCode'] = null;
        $arrMessage['ReturnText'] = null;

        if ($code==self::COMPLETE_SUCCESS_HAS_DATA) {
            $arrMessage['ReturnCode'] = self::COMPLETE_SUCCESS_HAS_DATA;
            $arrMessage['ReturnText'] = __('Valid');
        } else if ($code==self::COMPLETE_SUCCESS_NOT_DATA) {
            $arrMessage['ReturnCode'] = self::COMPLETE_SUCCESS_NOT_DATA;
            $arrMessage['ReturnText'] = __('Invalid');
        } else if ($code==self::MESSAGE_SYSTEM_ERROR) {
            $arrMessage['ReturnCode'] = self::MESSAGE_SYSTEM_ERROR;
            $arrMessage['ReturnText'] = __('System error');
        } else if ($code==self::MESSAGE_ERROR_CATEGORY) {
            $arrMessage['ReturnCode'] = self::MESSAGE_ERROR_CATEGORY_PROFILE;
            $arrMessage['ReturnText'] = __('The requested category does not exist');
            $arrMessage['ReturnParams'] = ['category_id'=>$dataInput];
        } else if ($code==self::MESSAGE_ERROR_PROFILE_ID) {
            $arrMessage['ReturnCode'] = self::MESSAGE_ERROR_CATEGORY_PROFILE;
            $arrMessage['ReturnText'] = __('The requested profile does not exist');
            $arrMessage['ReturnParams'] = [ 'profile_id'=>$dataInput];
        } else if ($code==self::MESSAGE_ERROR_WRONG_INPUT_FORMAT) {
            $arrMessage['ReturnCode'] = self::MESSAGE_ERROR_WRONG_INPUT_FORMAT;
            $arrMessage['ReturnText'] = __('Wrong input format');
        }
        return $arrMessage;
    }

    /**
     * Validate input format
     *
     * @return array|null
     */
    public function validateInputFormat() {
        $flagErrorFormat    = true ;
        $returnMessage      = [];

        $categoryIdError = $this->request->getParam('categoryId');
        $profileIdError  = $this->request->getParam('subprofileID');

        //validate wrong input format
        if (!is_numeric($categoryIdError) || $categoryIdError < 0) {
            $returnMessage[] = $this->getMessageError(self::MESSAGE_ERROR_WRONG_INPUT_FORMAT);
            $flagErrorFormat = false;
        }

        if (!is_numeric($profileIdError) || $profileIdError < 0) {
            $returnMessage[] = $this->getMessageError(self::MESSAGE_ERROR_WRONG_INPUT_FORMAT);
            $flagErrorFormat = false;
        }

        if ( !$flagErrorFormat ) {
            return [
                'ReturnMessage' => $returnMessage,
                'Products' => null
            ];
        }
        return null;
    }

    /**
     * Validate data exit
     *
     * @param $categoryId
     * @param $profileId
     *
     * @return array
     */
    public function validateNotExit($categoryId,$profileId)
    {

        $flagError       = true ;
        $category        = null;
        $returnMessage   = [];
        $categoryIdError = $this->request->getParam('categoryId');
        $profileIdError  = $this->request->getParam('subprofileID');

        try {
            $category = $this->categoryRepository->get($categoryId);
            if ( $category->getId()==null){

                $returnMessage[] = $this->getMessageError(self::MESSAGE_ERROR_CATEGORY,$categoryIdError);
                $flagError       = false;
            }
        } catch (\Exception $e) {
            $returnMessage[] = $this->getMessageError(self::MESSAGE_ERROR_CATEGORY,$categoryIdError);
            $flagError = false;
        }

        $subscriptionProfile = $this->getSubscriptionProfile($profileId);
        if ($subscriptionProfile == null){
            $returnMessage[] = $this->getMessageError(self::MESSAGE_ERROR_PROFILE_ID,$profileIdError);
            $flagError = false;
        }

        if (!$flagError ) {
            return [
                'ReturnMessage' => $returnMessage,
                'Products'      => null
            ];
        }

        return [
            'category'=>$category,
            'subscriptionProfile'=>$subscriptionProfile
        ];
    }

    /**
     * @param \Magento\Catalog\Model\Category $category
     * @return \Wyomind\AdvancedInventory\Model\ResourceModel\Product\Collection
     */
    protected function _getProductFromSubscriptionCourse(
        \Magento\Catalog\Model\Category $category
    ){
        /**
         *  if pass happy case, we will build product collection query to get exactly what we need
         */
        /** @var \Wyomind\AdvancedInventory\Model\ResourceModel\Product\Collection $categoryAssociatedProductCollection */
        $categoryAssociatedProductCollection = $category->setStoreId($this->_storeId)->getProductCollection();
        /* after get product collection add more filter init */
        $categoryAssociatedProductCollection->setVisibility($this->productVisibility->getVisibleInSiteIds())
            ->addFinalPrice()
            ->addAttributeToSelect(['name' ,'unit_qty','case_display' ,'sku' , 'unit_sap' , 'allow_spot_order'])
            ->addAttributeToFilter('available_subscription',array('eq' => 1))
            ->addAttributeToFilter('status',array('eq' => self::STATUS_ACTIVE));
        return $categoryAssociatedProductCollection;
    }

    /**
     * Get amount
     *
     * @param $entityItem
     * @return int
     */
    public function getAmount($entityItem)
    {
        $unitCase      = \Riki\CreateProductAttributes\Model\Product\CaseDisplay::CD_CASE_ONLY;
        $price         = (int)$entityItem->getPriceInfo()->getPrice('final_price')->getAmount()->getValue();
        $unitSap       = (int)$entityItem->getData('case_display');

        /**
         * If this product is "Case": Amount is case price (price*number of piece in 1 case) including tax
         */
        $amount  = $price;
        if ($unitCase == $unitSap){
            $unitQty  = (int) $entityItem->getData('unit_qty');
            if ($unitQty !=null){
                $amount   = $unitQty*$price;
            }
        }

        return (int)$amount;
    }

    /**
     * {@inheritdoc}
     */
    public function getAssignedProducts($categoryId,$profileId)
    {
        $returnMessage = [];
        $productItem   = [];

        //validate wrong input format
        $dataInputError = $this->validateInputFormat();
        if ($dataInputError != null) {
            return $dataInputError;
        }

        //validate data exit
        $dataValidateNotExit = $this->validateNotExit($categoryId, $profileId);
        if (isset($dataValidateNotExit['ReturnMessage'])) {
            return $dataValidateNotExit;
        }

        //validate success
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $dataValidateNotExit['category'];
        /** @var \Riki\Subscription\Model\Profile\Profile $subscriptionProfile */
        $subscriptionProfile = $dataValidateNotExit['subscriptionProfile'];

        $this->_storeId = $subscriptionProfile->getData('store_id');


        // register for catalog-rule
        $this->registry->unregister(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID);
        $this->registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $subscriptionProfile->getCourseId());
        $this->registry->unregister(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID);
        $this->registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $subscriptionProfile->getSubProfileFrequencyID());
        $this->registry->unregister('subscription_profile_obj');
        $this->registry->register('subscription_profile_obj', $subscriptionProfile);


        //check category not exit with subscription profile
        if ($subscriptionProfile != null){
            $arrCategoryIds = $this->resourceCoure->getCategoryIds($subscriptionProfile->getCourseId());
            if ( !in_array($categoryId,$arrCategoryIds) ) {
                return [
                    'ReturnMessage' => [
                            $this->getMessageError(self::COMPLETE_SUCCESS_NOT_DATA)
                    ],
                    'Products' => []
                ];
            }
        }

        $productCollection = $this->_getProductFromSubscriptionCourse($category);
        $this->stockHelper->addIsInStockFilterToCollection($productCollection);

        if ($productCollection->getSize() < 1) {
            //success not data
            return [
                'ReturnMessage' => [
                    $this->getMessageError(self::COMPLETE_SUCCESS_NOT_DATA)
                ],
                'Products' => []
            ];
        }
        //success has data
        $returnMessage[] = $this->getMessageError(self::COMPLETE_SUCCESS_HAS_DATA);

        /* going to loop */
        /** @var \Magento\Catalog\Model\Product $entityItem */
        foreach ($productCollection as $entityItem) {
            $isSalable = null; // reset each loop
            if ($entityItem->isSalable()) {
                $isSalable = true;
            } else {
                $isSalable = false;
            }

            $allowSpotOrder = $entityItem->getData('allow_spot_order');
            if (!$allowSpotOrder) {
                $isSalable = false;
            }

            $arrCatId = $entityItem->getCategoryIds() ;
            if (is_array($arrCatId) && count($arrCatId)>0 ){
                if (in_array($categoryId,$arrCatId)){
                    $productItem[] = [
                        "sku" => $entityItem->getSku(),
                        "position" => (int)$entityItem->getData('cat_index_position'),
                        "category_id" => $category->getId(),
                        "extension_attributes" => array(
                            'product_id' => $entityItem->getId(),
                            'unit' => $this->getProductUnit($entityItem),
                            'name' => $entityItem->getName(),
                            'price'  => (int)$this->getAmount($entityItem),
                            'amount' => $this->getAmount($entityItem),
                            'is_in_stock' => $isSalable
                        )
                    ];
                }
            }
        }
        
        return [
            'ReturnMessage' => $returnMessage,
            'Products' => $productItem
        ];
    }
}