<?php
namespace Riki\SubscriptionPage\Model;

use Magento\TestFramework\Event\Magento;
use Riki\SubscriptionPage\Api\PriceBoxInterface;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;
use Riki\Subscription\Model\Constant;
use Riki\Subscription\Helper\Order\Simulator;

class PriceBox extends \Magento\Framework\View\Element\Template implements PriceBoxInterface
{
    const ARRAY_KEY_QTY_SHOW = 'qty';
    const ARRAY_KEY_REAL_QTY = 'real_qty';
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $_layout;
    /**
     * @var \Magento\Framework\View\DesignInterface
     */
    protected $_design;

    /* @var \Riki\Subscription\Helper\Data */
    protected $_subHelperData;

    /* @var \Riki\SubscriptionCourse\Model\ResourceModel\Course */
    protected $_courseResourceModel;

    /* @var \Riki\SubscriptionCourse\Model\Course */
    protected $_courseModel;

    /* @var \Riki\SubscriptionCourse\Model\CourseFactory */
    protected $_courseFactory;

    /* @var \Riki\Subscription\Helper\Order\Simulator */
    protected $simulator;

    /* @var \Magento\Customer\Model\Session */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    protected $catalogRuleHelper;

    /**
     * PriceBox constructor.
     *
     * @param \Riki\SubscriptionCourse\Model\Course $courseModel
     * @param \Riki\SubscriptionCourse\Model\ResourceModel\Course $courseResourceModel
     * @param \Magento\Framework\View\DesignInterface $design
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Riki\Subscription\Helper\Data $_subHelperData
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $courseResourceModel,
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        \Magento\Framework\View\DesignInterface $design,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustmentCalculator,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Riki\Subscription\Helper\Data $_subHelperData,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Checkout\Model\Cart $cart,
        \Riki\Subscription\Model\Profile\Profile $profileModel,
        \Riki\CatalogRule\Helper\Data $catalogRuleHelper
    )
    {
        $this->_customerSession = $customerSession;
        $this->simulator = $simulator;
        $this->_courseFactory = $courseFactory;
        $this->_courseModel = $courseModel;
        $this->_courseResourceModel = $courseResourceModel;
        $this->_design = $design;
        $this->_layout = $layout;
        $this->_registry = $registry;
        $this->_productRepository = $productRepository;
        $this->_adjustmentCalculator = $adjustmentCalculator;
        $this->_priceCurrency = $priceCurrency;
        $this->_localeFormat = $localeFormat;
        $this->_storeManager = $storeManager;
        $this->_subHelperData = $_subHelperData;
        $this->messageManager = $messageManager;
        $this->cart = $cart;
        $this->profileModel = $profileModel;
        $this->catalogRuleHelper = $catalogRuleHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getList($courseId, $frequencyId, $iProfileId,$productCatIds, $productQtyIds, $selectedMachineId = 0)
    {
        $this->_design->setArea('frontend');
        $this->_design->setDefaultDesignTheme();

        if (empty($productCatIds)) {
            return [];
        }

        $productIds = [];
        foreach ($productCatIds as $item) {
            $parts = explode('_', $item);
            $productIds[] = $parts[0];
        }
        $this->catalogRuleHelper->registerPreLoadedProductIds($productIds);

        if($iProfileId){
            $profileModel = $this->profileModel->load($iProfileId);
            $this->_registry->register('subscription_profile_obj',$profileModel);
        }

        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);
        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);

        $response = [];
        $index = 0;

        $totalAmount = 0;
        foreach ($productCatIds as $productCatId) {
            if(strpos($productCatId,"_") !== false){
                list($iProductId,$iCatId) = explode("_",$productCatId);
                $product = $this->_productRepository->getById($iProductId);
                if (!$product->getId()) {
                    continue;
                }

                if(isset($productQtyIds[$index])){
                    $qTy = $productQtyIds[$index];
                    $product->setQty($qTy);
                }

                $response['product_price_' . $product->getId()] = $this->getProductPriceHtml($product);
                list($iAmount,$response['subtotal_item_' . $productCatId]) = $this->getFinalProductPrice($product);

                $totalAmount += $iAmount;
                $index++;
            }
        }

        if ($selectedMachineId) {
            $machine = $this->_productRepository->getById($selectedMachineId);
            if ($machine->getId()) {
                $machine->setQty(1);
                $machinePrice = $this->getFinalProductPrice($machine);
                $totalAmount += (int)$machinePrice[0];
            }
        }
        $response['total_amount'] = $this->getTotalAmountPrice($totalAmount);

        return \Zend_Json::encode($response);
    }

    /**
     * @param int $courseId
     * @param int $frequencyId
     * @param int $iProfileId
     * @param \string[] $productCatIds
     * @param \int[] $productQtyIds
     * @return array|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getPriceItem($courseId, $frequencyId,$iProfileId, $productCatIds, $productQtyIds)
    {
        $this->_design->setArea('frontend');
        $this->_design->setDefaultDesignTheme();

        if (empty($productCatIds)) {
            return [];
        }

        if($iProfileId){
            $profileModel = $this->profileModel->load($iProfileId);
            $this->_registry->register('subscription_profile_obj',$profileModel);
        }

        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);
        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);

        $response = [];
        $index = 0;

        foreach ($productCatIds as $productCatId) {

            if(strpos($productCatId,"_") !== false){
                list($iProductId,$iCatId) = explode("_",$productCatId);
                $product = $this->_productRepository->getById($iProductId);
                if (!$product->getId()) {
                    continue;
                }

                if(isset($productQtyIds[$index])){
                    $qTy = $productQtyIds[$index];
                    $product->setQty($qTy);
                }

                list($iAmount,$response['subtotal_item_' . $productCatId]) = $this->getFinalProductPrice($product);

                $index++;
            }
        }

        return \Zend_Json::encode($response);
    }

    /**
     * Return HTML block with product price
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getProductPriceHtml(\Magento\Catalog\Model\Product $product)
    {
        $price = '';
        /** @var \Magento\Framework\Pricing\Render $priceRender */
        $priceRender = $this->_layout->getBlock('product.price.render.default');
        if (!$priceRender) {
            $priceRender = $this->_layout->createBlock(
                'Magento\Framework\Pricing\Render',
                'product.price.render.default',
                ['data' => [
                    'price_render_handle' => 'catalog_product_prices',
                    'use_link_for_as_low_as' => true,
                ]]
            );
        }
        if ($priceRender) {
            $price = $priceRender->render(
                \Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE,
                $product,
                []
            );
        }
        return $this->minifyHtml($price);
    }

    /**
     * Get product hanpukai config
     *
     * @param $courseId
     *
     * @return array
     */
    public function getHanpukaiProductConfig($courseId)
    {
        $resultArr = array();
        $courseModel = $this->_courseModel->load($courseId);
        if ($courseModel instanceof \Riki\SubscriptionCourse\Model\Course) {
            if ($courseModel->getData('hanpukai_type') == SubscriptionType::TYPE_HANPUKAI_FIXED) {
                $arrProduct = $this->_courseResourceModel->getHanpukaiFixedProductsDataPieCase($courseModel);
                foreach($arrProduct as $key => $value) {
                    $resultArr[$key]['qty'] = $value['qty'];
                    $resultArr[$key]['unit_qty'] = $value['unit_qty'];
                    $resultArr[$key]['unit_case'] = $value['unit_case'];
                }
            }

            if ($courseModel->getData('hanpukai_type') == SubscriptionType::TYPE_HANPUKAI_SEQUENCE) {
                $arrProduct = $this->_courseResourceModel->getHanpukaiSequenceProductsData($courseModel);
                $firstDelivery = $this->getFirstDeliveryNumber($arrProduct);
                foreach ($arrProduct as $key => $value) {
                    if ($value['delivery_number'] == $firstDelivery) {
                        $resultArr[$key]['qty'] = $value['qty'];
                        $resultArr[$key]['unit_case'] = $value['unit_case'];
                        $resultArr[$key]['unit_qty'] = $value['unit_qty'];
                    }
                }
            }

        }

        return $resultArr;
    }

    /**
     * Get first delivery number
     *
     * @param $arrProduct
     *
     * @return mixed
     */
    public function getFirstDeliveryNumber($arrProduct)
    {
        $deliveryNumberArr = array();
        foreach ($arrProduct as $key => $value) {
            if(isset($value['delivery_number'])) {
                $deliveryNumberArr[] = $value['delivery_number'];
            }
        }
        $deliveryNumberArr = $this->sort($deliveryNumberArr, count($deliveryNumberArr));
        return $deliveryNumberArr[0];
    }

    /**
     * Sort
     *
     * @param $arr
     * @param $length
     *
     * @return mixed
     */
    public function sort($arr, $length)
    {
        for($i=0; $i < $length - 1; $i++) {
            for($j = $i+1 ; $j < $length; $j++) {
                if((int)$arr[$j] < (int)$arr[$i]) {
                    $tmp = $arr[$i];
                    $arr[$i] = $arr[$j];
                    $arr[$j] = $tmp;
                }
            }
        }
        return $arr;
    }


    /**
     * Return HTML block with product price
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getFinalProductPrice(\Magento\Catalog\Model\Product $product)
    {
        $price = 0;

        if($product->getQty()){

            if($product->getTypeId() == \Magento\Catalog\Model\Product\Type::TYPE_BUNDLE){

                $amount = $this->_subHelperData->getBundleMaximumPrice($product);
                $price = $amount * $product->getQty();

            }
            else{
                $amount = $this->_adjustmentCalculator->getAmount($product->getFinalPrice($product->getQty()), $product)->getValue();
                $amount = $this->_priceCurrency->format($amount, false, \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION);
                $amount = $this->_localeFormat->getNumber($amount);
                $_p = $amount * $product->getQty();

                $price = $price + $_p;

            }
        }

        return array
        (
            $price,
            $this->_priceCurrency->convertAndFormat(
                $price,
                true,
                \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
                $this->_storeManager->getStore()
            )
        );
    }

    /**
     * Return HTML block with product price
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getTotalAmountPrice($amount)
    {

        return $this->_priceCurrency->convertAndFormat(
            $amount,
            true,
            \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
            $this->_storeManager->getStore()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getListMachines($courseId, $frequencyId, $machineIds)
    {
        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);
        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);

        $result = [];
        foreach ($machineIds as $id) {
            try {
                $product = $this->_productRepository->getById($id);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                continue;
            }
            $product->setQty(1);
            $fullPrice = $this->getFinalProductPrice($product);
            $result[] = [
                'id' => $id,
                'text' => $product->getName() . ', ' . strip_tags($fullPrice[1]),
                'price' => (int)$fullPrice[0]
            ];
        }
        return \Zend_Json::encode($result);
    }

    /**
     * Get product hanpukai qty html
     *
     * @param $arrProductConfig
     * @param $product
     * @param $changeAllQty
     *
     * @return array
     */
    public function getProductHanpukaiChangeQty($arrProductConfig, $product, $changeAllQty)
    {

        /* @var $product \Magento\Catalog\Model\Product */
        $productArrInfo = $arrProductConfig[$product->getId()];
        $qty = (int)$productArrInfo['qty'] * (int)$changeAllQty;
        $arrResult[self::ARRAY_KEY_QTY_SHOW] = $qty;
        $arrResult[self::ARRAY_KEY_REAL_QTY] = $qty;
        $arrResult['unit_qty'] = isset($productArrInfo['unit_qty'])?$productArrInfo['unit_qty']:1;
        return $arrResult;
    }

    /**
     * Change qty for all set hanpukai
     *
     * @param int $courseId
     * @param int $frequencyId
     * @param $productCatIds
     * @param $qtyChangeAll
     *
     * @return array|string
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function changeHanpukaiQty($courseId, $frequencyId, $productCatIds, $qtyChangeAll)
    {
        $arrProductInfoForHanpukai = array();
        $arrProductInfoForHanpukai['customer_id'] = null;
        $arrProductInfoForHanpukai[Constant::RIKI_COURSE_ID] = $courseId;
        $arrProductInfoForHanpukai[Constant::RIKI_FREQUENCY_ID] = $frequencyId;
        if ($this->_customerSession->isLoggedIn()) {
            $arrProductInfoForHanpukai['customer_id'] = $this->_customerSession->getCustomerId();
        }
        $arrHanpukaiProductConfig = $this->getHanpukaiProductConfig($courseId);
        $this->_design->setArea('frontend');
        $this->_design->setDefaultDesignTheme();

        if (empty($productCatIds)) {
            return [];
        }
        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);
        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);

        $response = [];
        $index = 0;

        $totalAmount = 0;
        foreach ($productCatIds as $productCatId) {

            if(strpos($productCatId,"_") !== false){
                list($iProductId,$iCatId) = explode("_",$productCatId);
                $product = $this->_productRepository->getById($iProductId);
                if (!$product->getId()) {
                    continue;
                }
                $arrProductQty = $this->getProductHanpukaiChangeQty($arrHanpukaiProductConfig, $product, $qtyChangeAll);
                $product->setQty($arrProductQty[self::ARRAY_KEY_REAL_QTY]);
                $arrProductInfoForHanpukai['product_info'][$index]['qty'] = (int)$arrProductQty[self::ARRAY_KEY_QTY_SHOW]/$arrProductQty['unit_qty'];
                $arrProductInfoForHanpukai['product_info'][$index]['product_id'] = $iProductId;
                $arrProductInfoForHanpukai['product_info'][$index]['product_type'] = $product->getTypeId();
                $arrProductInfoForHanpukai['product_info'][$index]['bundle_option_qty'] = array();
                $arrProductInfoForHanpukai[Constant::RIKI_COURSE_ID] = $courseId;
                $arrProductInfoForHanpukai['frequency'] = $frequencyId;
                $arrProductInfoForHanpukai['hanpukai_change_set_qty'] = $qtyChangeAll;
                $response['product_price_' . $product->getId()] = $this->getProductPriceHtml($product);
                $response['qty_selected_'.$productCatId] = $arrProductQty[self::ARRAY_KEY_REAL_QTY];
                list($iAmount,$response['subtotal_item_' . $productCatId]) = $this->getFinalProductPrice($product);
                $response['product_hanpukai_qty_'.$productCatId] = $arrProductQty[self::ARRAY_KEY_QTY_SHOW];
                $response['product_hanpukai_qty_case_'.$productCatId] = (int)$arrProductQty[self::ARRAY_KEY_QTY_SHOW]/$arrProductQty['unit_qty'];
                $totalAmount += $iAmount;
                $index++;
            }
        }

        $courseObj = $this->_courseFactory->create()->load($courseId);
        if ($courseObj->getData('subscription_type') == SubscriptionType::TYPE_HANPUKAI) {
            $quoteSimulate = $this->simulator->createMageQuote($arrProductInfoForHanpukai, true);
            if ($quoteSimulate instanceof \Magento\Framework\DataObject) {
                $response['total_amount']
                    = $this->formatCurrency($quoteSimulate->getData('grand_total'));
            } else {
                $response['total_amount'] = 'error simulate quote';
            }
        } else {
            $response['total_amount'] = $this->getTotalAmountPrice($totalAmount);
        }


        return \Zend_Json::encode($response);
    }

    /**
     * Format Prices
     *
     * @param $price
     * @param null $websiteId
     *
     * @return mixed
     */
    public function formatCurrency($price)
    {
        return $this->_storeManager->getWebsite($this->_storeManager->getStore()->getWebsiteId())
            ->getBaseCurrency()->format($price);
    }

    /**
     * Minify html string
     *
     *
     * @param $html
     *
     * @return string
     */
    public function minifyHtml($html)
    {
        $inlineHtmlTags = [
            'b',
            'big',
            'i',
            'small',
            'tt',
            'abbr',
            'acronym',
            'cite',
            'code',
            'dfn',
            'em',
            'kbd',
            'strong',
            'samp',
            'var',
            'a',
            'bdo',
            'br',
            'img',
            'map',
            'object',
            'q',
            'span',
            'sub',
            'sup',
            'button',
            'input',
            'label',
            'select',
            'textarea',
            '\?',
        ];
        $html = preg_replace(
            '#(?<!]]>)\s+</#',
            '</',
            preg_replace(
                '#((?:<\?php\s+(?!echo|print|if|elseif|else)[^\?]*)\?>)\s+#',
                '$1 ',
                preg_replace(
                    '#(?<!' . implode('|', $inlineHtmlTags) . ')\> \<#',
                    '><',
                    preg_replace(
                        '#(?ix)(?>[^\S ]\s*|\s{2,})(?=(?:(?:[^<]++|<(?!/?(?:textarea|pre|script)\b))*+)'
                        . '(?:<(?>textarea|pre|script)\b|\z))#',
                        ' ',
                        preg_replace(
                            '#(?<!:|\\\\|\'|")//(?!\s*\<\!\[)(?!\s*]]\>)[^\n\r]*#',
                            '',
                            preg_replace(
                                '#(?<!:|\'|")//[^\n\r]*(\s\?\>)#',
                                '$1',
                                preg_replace(
                                    '#(?<!:)//[^\n\r]*(\<\?php)[^\n\r]*(\s\?\>)[^\n\r]*#',
                                    '',
                                    $html
                                )
                            )
                        )
                    )
                )
            )
        );

        return $html;
    }

    /**
     * validateAdditionalCat
     *
     * @param int $courseId
     * @param int $frequencyId
     * @param \string[] $selectedMain
     * @return bool
     */
    public function validateAdditionalCat($courseId,$frequencyId,$selectedMain){

        $response = [];
        $isValid = true;
        $sMessage = '';

        if(!$frequencyId){
            $isValid = false;
            $sMessage = __('Please select the interval of the subscription.');
            $this->messageManager->addError($sMessage);
        }

        $response['is_valid'] = $isValid;
        $response['message'] = $sMessage;

        return \Zend_Json::encode($response);
    }
}
