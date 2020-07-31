<?php


namespace Riki\Subscription\Block\Profiles;

use Riki\CreateProductAttributes\Model\Product\CaseDisplay;
use Riki\Subscription\Helper\Profile\CampaignHelper;

class Confirm extends \Riki\Subscription\Block\Frontend\Profile\ConfirmSpotProduct
{
    /**
     * item list of profile
     *
     * @var array
     */
    protected $newSpotProducts;

    /**
     * @var \Riki\Subscription\Helper\Profile\CampaignHelper
     */
    protected $campaignHelper;

    /**
     * @var \Riki\Subscription\Model\Multiple\Category\Cache
     */
    protected $multipleCategoryCache;

    /**
     * Confirm constructor.
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Model\AddressFactory $customerAddress
     * @param \Riki\DeliveryType\Model\DeliveryDate $deliveryDate
     * @param \Magento\Catalog\Helper\Image $image
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustment
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Subscription\Helper\Order\Simulator $simulator
     * @param \Riki\Subscription\Block\Adminhtml\Profile\ConfirmSpotProduct $backendConfirm
     * @param \Riki\Subscription\Helper\Profile\Data $profileHelper
     * @param \Riki\SubscriptionCourse\Model\Course $courseModel
     * @param CaseDisplay $caseDisplay
     * @param \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper
     * @param \Riki\Subscription\Helper\Data $subscriptionHelper
     * @param \Riki\StockPoint\Helper\Data $stockPointHelper
     * @param \Riki\Subscription\Helper\StockPoint\Data $stockPointData
     * @param \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData
     * @param \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper
     * @param \Riki\Subscription\Model\Multiple\Category\Cache $multipleCategoryCache
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\AddressFactory $customerAddress,
        \Riki\DeliveryType\Model\DeliveryDate $deliveryDate,
        \Magento\Catalog\Helper\Image $image,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\Adjustment\CalculatorInterface $adjustment,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Riki\Subscription\Block\Adminhtml\Profile\ConfirmSpotProduct $backendConfirm,
        \Riki\Subscription\Helper\Profile\Data $profileHelper,
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        CaseDisplay $caseDisplay,
        \Riki\SubscriptionFrequency\Helper\Data $frequencyHelper,
        \Riki\Subscription\Helper\Data $subscriptionHelper,
        \Riki\StockPoint\Helper\Data $stockPointHelper,
        \Riki\Subscription\Helper\StockPoint\Data $stockPointData,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        \Riki\Subscription\Helper\Profile\CampaignHelper $campaignHelper,
        \Riki\Subscription\Model\Multiple\Category\Cache $multipleCategoryCache,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $registry,
            $customerAddress,
            $deliveryDate,
            $image,
            $productRepository,
            $adjustment,
            $priceCurrency,
            $localeFormat,
            $searchCriteriaBuilder,
            $simulator,
            $backendConfirm,
            $profileHelper,
            $courseModel,
            $caseDisplay,
            $frequencyHelper,
            $subscriptionHelper,
            $stockPointHelper,
            $stockPointData,
            $buildStockPointPostData,
            $data
        );
        $this->campaignHelper = $campaignHelper;
        $this->multipleCategoryCache = $multipleCategoryCache;
    }

    /**
     * Set page title
     *
     * @return mixed
     */
    public function _prepareLayout()
    {
        $pageTitle = "Subscription Course Change";
        $this->pageConfig->getTitle()->set(__($pageTitle));
        return parent::_prepareLayout();
    }

    /**
     * Get summer campaign data from session manager
     *
     * @return mixed
     */
    public function getSummerCampaignData()
    {
        return $this->_registry->registry(CampaignHelper::SUMMER_CAMPAIGN_DATA);
    }

    /**
     * Get summer campaign cache id
     *
     * @return mixed
     */
    public function getSummerCampaignCacheId()
    {
        return $this->_registry->registry(CampaignHelper::SUMMER_CAMPAIGN_CACHE_ID);
    }

    /**
     * Get profileId
     *
     * @return mixed
     */
    public function getProfile()
    {
        return $this->_registry->registry(CampaignHelper::PROFILE);
    }

    /**
     * Get request data
     *
     * @return bool|mixed
     */
    public function getReqData()
    {
        $summerCampaignData = $this->getSummerCampaignData();

        return isset($summerCampaignData['reqdata']) ? $summerCampaignData['reqdata'] : false;
    }

    /**
     * Get order simulate
     *
     * @return object|bool
     */
    public function getOrderSimulate()
    {
        $identifier = $this->getSummerCampaignCacheId();
        $simulatedOrder = $this->multipleCategoryCache->getCache($identifier);

        if (!$simulatedOrder) {
            $simulatedOrder = $this->campaignHelper->simulator($this->getProfile(), $this->getNewSpotProducts());
        }

        return $simulatedOrder;
    }

    /**
     * Get list SPOT product which is added to Subscription
     *
     * @return mixed
     */
    public function getNewSpotProducts()
    {
        if (!$this->newSpotProducts) {
            $multipleCategoryData = $this->getSummerCampaignData();
            $arrayProduct = $multipleCategoryData['new_spot_products'];

            // Get product collection
            $productCollection = $this->campaignHelper->getProductCollectionByIds(array_keys($arrayProduct));

            foreach ($productCollection as $id => $product) {
                $unitQty = 1;

                if ($product->getData('case_display') == CaseDisplay::CD_CASE_ONLY
                    && $product->getData('unit_qty')
                ) {
                    $unitQty = (int)$product->getData('unit_qty');
                }

                $unit = $this->_caseDisplay->getCaseDisplayText(
                    $product->getData('case_display')
                );

                // qty of spot product will be add to profile - based on piece data
                $qtyToAssigned = $arrayProduct[$id];
                // qty based on unit
                $qtyBasedOnUnit = (int)($qtyToAssigned / $unitQty);

                // product price
                $amount = (float)$this->getAmount($product, $qtyToAssigned) * $unitQty;

                // total amount
                $totalAmount = (float)($amount * ($qtyToAssigned / $unitQty));

                $this->newSpotProducts[$id]['product'] = $product;
                $this->newSpotProducts[$id]['qty_assigned'] = $qtyToAssigned;
                $this->newSpotProducts[$id]['qty_request'] = $qtyBasedOnUnit;
                $this->newSpotProducts[$id]['qty_unit'] = $qtyBasedOnUnit . ' ' . $unit;
                $this->newSpotProducts[$id]['amount'] = $this->formatCurrency($amount);
                $this->newSpotProducts[$id]['total_amount'] = $this->formatCurrency($totalAmount);
            }
        }

        return $this->newSpotProducts;
    }
}