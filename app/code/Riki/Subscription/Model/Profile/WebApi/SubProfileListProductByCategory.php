<?php
namespace Riki\Subscription\Model\Profile\WebApi;

use Magento\Setup\Exception;
use  Riki\Subscription\Api\WebApi\SubProfileListProductByCategoryInterface;
use Riki\SubscriptionCourse\Model\ResourceModel\Course as SubscriptionCourseResourceModel;
use Magento\Tax\Model\Calculation;
use Magento\Framework\Webapi\Rest\Request as RestRequest;

class SubProfileListProductByCategory implements SubProfileListProductByCategoryInterface
{
    const CATEGORY_RECOMMEND = 'subscriptioncourse/categories_recommend/category_recommend';

    /* @var \Riki\SubscriptionCourse\Model\ResourceModel\Course */
    protected $_subCourseResourceModel;

    /**
     * @var
     */
    protected $profileModel;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Tax\Model\TaxCalculation
     */
    protected $taxCalculation;

    /**
     * @var \Magento\Catalog\Helper\ImageFactory
     */
    private $imageFactory;

    /**
     * Tax calculation tool
     *
     * @var Calculation
     */
    protected $calculationTool;

    /**
     * @var RestRequest
     */
    protected $request;

    /**
     * @var \Magento\Catalog\Block\Product\ImageBuilder
     */
    protected $imageBuilder;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    private $appEmulation;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    private $profileFactory;

    /**
     * @var \Riki\Catalog\Helper\Data
     */
    private $catalogHelper;


    /**
     * SubProfileListProductByCategory constructor.
     * @param \Riki\SubscriptionCourse\Model\Course $subCourseModel
     * @param SubscriptionCourseResourceModel $subCourseResourceModel
     * @param \Magento\Tax\Model\TaxCalculation $taxCalculation
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Helper\ImageFactory $imageHelperFactory
     * @param Calculation $calculationTool
     * @param RestRequest $request
     * @param \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder
     * @param \Magento\Store\Model\App\Emulation $appEmulation
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\Catalog\Helper\Data $catalogHelper
     */
    public function __construct(
        \Riki\SubscriptionCourse\Model\Course $subCourseModel,
        SubscriptionCourseResourceModel $subCourseResourceModel,
        \Magento\Tax\Model\TaxCalculation $taxCalculation,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Helper\ImageFactory $imageHelperFactory,
        Calculation $calculationTool,
        RestRequest $request,
        \Magento\Catalog\Block\Product\ImageBuilder $imageBuilder,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Catalog\Helper\Data $catalogHelper
    ) {
        $this->_subCourseModel = $subCourseModel;
        $this->_subCourseResourceModel = $subCourseResourceModel;
        $this->taxCalculation = $taxCalculation;
        $this->scopeConfig = $scopeConfig;
        $this->imageFactory = $imageHelperFactory;
        $this->calculationTool = $calculationTool;
        $this->request = $request;
        $this->imageBuilder = $imageBuilder;
        $this->appEmulation = $appEmulation;
        $this->storeManager = $storeManager;
        $this->profileFactory = $profileFactory;
        $this->catalogHelper = $catalogHelper;
    }

    /**
     * @param $product
     * @return string
     */
    protected function getImage($product)
    {
        $result = $this->imageBuilder->create($product,
            'cart_page_product_thumbnail')->getImageUrl();
        return $result;
    }

    /**
     * @param $courseId
     * @param $categoryId
     * @return bool
     */
    private function validateParams($courseId, $categoryId) {
        if (empty($courseId) || empty($categoryId)) {
            return false;
        }
        return true;
    }

    /**
     * @param string $courseId
     * @param string $categoriesId
     * @param string $page
     * @param string $limit
     * @param string $isCategoryHomePage
     * @return array|mixed
     */
    public function getListProductByCategories($courseId, $categoriesId, $page, $limit, $isCategoryHomePage) {

        $this->appEmulation->startEnvironmentEmulation($this->storeManager->getStore()->getId(),
            \Magento\Framework\App\Area::AREA_FRONTEND, true);

        $errorCode = 'SU00';
        $errorMsg = __('Valid');
        $returnData = null;

        if (!$this->validateParams($courseId, $categoriesId)) {
            return [
                'return_message' => [
                    [
                        'code' => 'ER001',
                        'message' => __('param is missing')
                    ]
                ],
                'listProduct' => $returnData
            ];
        }


        if (!is_array($categoriesId)) {
            $categoryId = explode(",", $categoriesId);
        }
        try {
            $arrProductGroupByCategory = $this->_subCourseResourceModel->getListOfProductGroupByCategoryAppReact($courseId, $categoryId, $page, $limit, $isCategoryHomePage);
            $listProductTmp = [];

            foreach ($arrProductGroupByCategory as $categoryId => $products) {
                foreach ($products as $product) {
                    $tierPricesTmp = '';
                    $tierPricesArr = [];

                    $productTmp = $product->getData();
                    $stockMessage = $this->_subCourseResourceModel->getStockStatusMessage($product);

                    if (array_key_exists('class', $stockMessage)
                        && array_key_exists('message', $stockMessage)
                    ) {
                        $productTmp['class_message'] = $stockMessage['class'];
                        $productTmp['text_message'] = __('Stock:') .' '. $stockMessage['message'];
                        $productTmp['stock_message'] = __('Stock:') .' '. $stockMessage['message'];
                    } else {
                        $productTmp['class_message'] = '';
                        $productTmp['text_message'] = '';
                        $productTmp['stock_message'] = '';
                    }
                    $isInStock = $product->getIsSalable();
                    if ($isInStock == false) {
                        $productTmp['text_message'] = __('Stock:') .' '. $this->_subCourseResourceModel->getOutStockMessageByProduct($product);
                        $productTmp['stock_message'] = __('Stock:') .' '. $this->_subCourseResourceModel->getOutStockMessageByProduct($product);
                    }
                    $productTmp['stock_status'] = $isInStock ? 1 : 0;
                    $productTmp['product_id'] = $product->getData('entity_id');
                    $productTmp['unit_case'] = ($product->getCaseDisplay() == 1) ? 'EA' : 'CS';
                    $productTmp['unit_qty'] = 1;

                    if ($product->getCaseDisplay() == 2) {
                        $productTmp['unit_qty'] = (null != $product->getUnitQty()) ? $product->getUnitQty() : "1";
                    }

                    $productTmp['label_list'] = $this->getProductLabel($product);
                    $productTmp['name'] = mb_strimwidth(trim($product->getName()), 0, 65, "...");
                    $productTmp['product_type'] =$this->_subCourseResourceModel->getProductType($product);
                    $productTmp['special_price_incl_tax_text'] =  $this->getPriceInclTax ($product, $product->getData('special_price'), true);
                    $productTmp['special_price_incl_tax'] =  $this->getPriceInclTax ($product, $product->getData('special_price'), false);

                    $productTmp["final_price_incl_tax_text"] = $this->calculationTool->round($product->getPriceInfo()->getPrice('final_price')->getValue()) . '円';
                    $productTmp["final_price_incl_tax"] = $this->calculationTool->round($product->getPriceInfo()->getPrice('final_price')->getValue());
                    $productTmp["price"] = $this->calculationTool->round($product->getPriceInfo()->getPrice('final_price')->getValue());
                    if ( $productTmp['product_type'] != 'bundle') {
                        $productTmp["final_price_incl_tax"] =  $this->getPriceInclTax ($product, $product->getPriceInfo()->getPrice('final_price')->getValue());
                        $productTmp["final_price_incl_tax_text"] = $this->getPriceInclTax ($product, $product->getPriceInfo()->getPrice('final_price')->getValue(), true);
                        $productTmp["price"] = $this->getPriceInclTax ($product, $product->getPriceInfo()->getPrice('final_price')->getValue());
                    }
                    $productTmp["thumbnail_image_url"] = $this->getImage($product);
                    $productTmp["thumbnail"] = $this->getImage($product);

                    $productTmp['category_id'] = $categoryId;

                    $getTierPrices = $product->getTierPrices();
                    if(count($getTierPrices) > 0) {
                        foreach($getTierPrices as $key => $pirces) {
                            $priceInclTax = $this->getPriceInclTax($product, $pirces->getValue(), false);
                            if ($priceInclTax < $productTmp["final_price_incl_tax"]) {
                                $tierPricesArr[$key] = ['tier_qty' => $pirces->getQty(), 'tier_price' => $priceInclTax];
                                if('CS' == $productTmp['unit_case']) {
                                    $tierPricesTmp .= __(
                                        '%1 ケース: %2 / %3',
                                        ceil($pirces->getQty() / $productTmp['unit_qty']),
                                        $this->getPriceInclTax($product, $pirces->getValue(), true),
                                        __($productTmp['unit_qty'])
                                    ) . '<br/>';
                                }
                                if('EA' == $productTmp['unit_case']) {
                                    $tierPricesTmp .=  __(
                                        '%1 個セット: %2 / %3',
                                        ceil($pirces->getQty()/$productTmp['unit_qty']),
                                        $this->getPriceInclTax($product, $pirces->getValue(), true),
                                        __($productTmp['unit_case'])
                                    ) . '<br/>';
                                }
                            }
                        }
                    }
                    $productTmp['tier_price_text'] = $tierPricesTmp;
                    $productTmp['tier_price_data'] = $tierPricesArr;
                    $productTmp['url'] = $product->getProductUrl();
                    $listProductTmp[$categoryId][] = $productTmp;
                }
            }
        } catch (\Exception $e) {
            return [
                'return_message' => [
                    [
                        'code' => $e->getCode(),
                        'message' => $e->getMessage()
                    ]
                ],
                'listProduct' => []
            ];
        }

        return [
            'return_message' => [
                [
                    'code' => $errorCode,
                    'message' => $errorMsg
                ]
            ],
            'listProduct' => $listProductTmp
        ];
    }

    /**
     * @param $profileId
     * @throws \Exception
     */
    protected function getCourse($profileId) {
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->profileFactory->create()->load($profileId);
        return $profile;
    }
    /**
     * @param string $profileId
     * @return mixed[]|SubscriptionCourseResourceModel
     */
    public function getListCategories($profileId) {
        $this->appEmulation->startEnvironmentEmulation($this->storeManager->getStore()->getId(),
            \Magento\Framework\App\Area::AREA_FRONTEND, true);
        $courseData = $this->getCourse($profileId);
        $courseId = $courseData->getCourseId();
        $frequencyId = $courseData->getSubProfileFrequencyID();
        $listCategory = $this->_subCourseResourceModel->getListCategoriesAppReact($arrCategoryAditionalId, $arrProfileCategoryIds, $courseId, true );
        foreach ($listCategory as $key => $value) {
            $listCategory[$key]['is_aditional'] = false;
            $listCategory[$key]['is_edit_profile_category'] = false;
            if (in_array($value['entity_id'], $arrCategoryAditionalId)) {
                $listCategory[$key]['is_aditional'] = true;
            }
            if (in_array($value['entity_id'], $arrProfileCategoryIds)) {
                $listCategory[$key]['is_edit_profile_category'] = true;
            }
            if (isset($value['description'])) {
                $listCategory[$key]['description'] = $this->replaceContentImage($value['description'], $this->getMediaBaseUrl());
            }
            if (isset($value['image'])) {
                $listCategory[$key]['image'] = $this->getMediaBaseUrl() . 'catalog/category/' . $value['image'];
            }
            $listCategory['courseData'] = ['courseId' => $courseId, 'frequencyId' => $frequencyId];
        }
        return $listCategory;
    }

    /**
     * @param $product
     * @param $price
     * @return float|int
     */
    private function getPriceInclTax($product, $price, $string = false) {
        if ($taxAttribute = $product->getCustomAttribute('tax_class_id')) {
            $productRateId = $taxAttribute->getValue();
            $rate = $this->taxCalculation->getCalculatedRate($productRateId);
            if ((int) $this->scopeConfig->getValue(
                    'tax/calculation/price_include_tax',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                ) === 1
            ) {
                $priceExclTax = $price / (1 + ($rate / 100));
            } else {
                $priceExclTax = $price;
            }
            $priceInclTax = $priceExclTax + ($priceExclTax * ($rate / 100));
            $priceInclTax = $this->calculationTool->round($priceInclTax);
            $priceInclTax = ($string) ? $priceInclTax . '円' : $priceInclTax;
            return $priceInclTax;
        }
        return 0;
    }

    /**
     * @param \Magento\Catalog\Model\Product $product
     */
    private function getProductLabel($product)
    {
        $label = [];

        //Delivery Type
        $label[] = [
            'type' => $product->getDeliveryType()
        ];

        //Free label
        if ($product->getIsFreeShipping()) {
            $label[] = [
                'type' => 'free_shipping'
            ];
        }

        if ($this->catalogHelper->hasGiftWrapping($product)) {
            if ($this->catalogHelper->hasFreeGiftWrapping($product)) {
                $label[] = [
                    'type' => 'wrapping_free'
                ];

            } else {
                $label[] = [
                    'type' => 'wrapping_available'
                ];
            }
        }

        return $label;
    }

    /**
     * @param string $categoriesId
     * @return mixed|void
     */
    public function getListCategoriesRecommend($categoriesId)
    {
        if (!is_array($categoriesId)  && $categoriesId != 'all') {
            $categoriesId = explode(",", $categoriesId);
        } else {
            $categoriesId = explode(",", $this->getConfigCategoriesRecommed());
        }
        $listCategory = $this->_subCourseResourceModel->getListCategoriesRecommendAppReact($categoriesId);

        foreach ($listCategory as $key => $value) {
            if (isset($value['description'])) {
                $listCategory[$key]['description'] = $this->replaceContentImage($value['description'], $this->getMediaBaseUrl());
            }
            if (isset($value['image'])) {
                $listCategory[$key]['image'] = $this->getMediaBaseUrl() . 'catalog/category/' . $value['image'];
            }
        }

        return $listCategory;
    }

    /**
     * @param null $store
     *
     * @return mixed
     */
    public function getConfigCategoriesRecommed($store = null)
    {
        return $this->scopeConfig->getValue(
            self::CATEGORY_RECOMMEND,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * @param $content
     * @param $mediaBaseUrl
     * @return string|string[]
     */
    private function replaceContentImage($content, $mediaBaseUrl)
    {
        preg_match('/<([a-z0-9\-\_]+[^>]+?)([a-z0-9\-\_]+="[^"]*?\{\{.+?\}\}.*?".*?)>/i', $content, $matches);

        if (isset($matches[1]) && strpos($matches[1], 'img') !== false) {
            $attributesString = $matches[2];
            preg_match('/([a-z0-9\-\_]+)="(.*?)(\{\{.+?\}\})(.*?)"/i', $attributesString, $matches2);
            $decodedDirectiveString = preg_replace('/&quot;/', '"', $matches2[3]);
            $decodedDirectiveString = preg_replace('/{{|}}|media|url=|"|\s/','',$decodedDirectiveString);
            $imageSrc = $matches2[1] . '="' . $matches2[2] . $mediaBaseUrl . $decodedDirectiveString . $matches2[4] . '"';
            $content = $content = str_replace($matches2[0], $imageSrc, $content);
        }
        return $content;
    }
    /**
     * @return mixed
     */
    public function getMediaBaseUrl()
    {
        $currentStore = $this->storeManager->getStore();
        return $currentStore->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }
}
