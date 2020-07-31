<?php

namespace Riki\Catalog\Block\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;

class View extends \Magento\Catalog\Block\Product\View
{
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_dateTime;

    /**
     * @var \Riki\Subscription\Model\Profile\Profile
     */
    protected $_profileModel;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_profileHelper;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;

    protected $httpContext;

    protected $customerUrl;

    /**
     * @var \Riki\Customer\Helper\SsoUrl
     */
    protected $ssoUrl;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session $customerSession,
        ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\Subscription\Model\Profile\Profile $profileModel,
        \Riki\Subscription\Helper\Profile\Data $profileHelper,
        \Magento\Framework\App\Http\Context $httpContext,
        \Magento\Customer\Model\Url $customerUrl,
        \Riki\Customer\Helper\SsoUrl $ssoUrl,
        array $data = []
    ) {
        parent::__construct($context,$urlEncoder,$jsonEncoder,$string,$productHelper,$productTypeConfig,$localeFormat,$customerSession,$productRepository,$priceCurrency,$data);
        $this->_dateTime = $dateTime;
        $this->_timezone = $context->getLocaleDate();
        $this->_profileModel = $profileModel;
        $this->_profileHelper = $profileHelper;
        $this->httpContext = $httpContext;
        $this->customerUrl = $customerUrl;
        $this->ssoUrl = $ssoUrl;
    }

    public function getUnitDisplay()
    {

        $_product = $this->getProduct();
        if($_product->getCaseDisplay() == 1){
            return array('ea' => __('EA'));
        }
        else
        if($_product->getCaseDisplay() == 2){
            return array('cs' => __('CS').'('.$this->getUnitConvertPieceCase().' '.__('EA').')');
        }
        else
        if($_product->getCaseDisplay() == 3){
            return array('ea' => __('EA'),'cs' => __('CS').'('.$this->getUnitConvertPieceCase().' '.__('EA').')');
        }
        else{
            return array('ea' => __('EA'));
        }
    }

    public function getUnitConvertPieceCase()
    {
        $_product = $this->getProduct();
        return $_product->getUnitQty();
    }

    public function getAddToCartLabel()
    {
        return __('Add To Cart');
    }

    /**
     * Get data json to render on template
     *
     * @return string
     */
    public function getProfileDataJson()
    {
        $result = [];
        if ($customerId = $this->_coreRegistry->registry('customer_id')) {
            $profiles = $this->_profileModel->getCustomerSubscriptionProfileExcludeHanpukai($customerId);
            if ($profiles->getSize()) {
                foreach ($profiles as $key => $profile) {
                    // If profile is monthly, don't show it in popup when add spot product in product detail page FO.
                    if ($profile->getData('subscription_type') == SubscriptionType::TYPE_MONTHLY_FEE) {
                        $profiles->removeItemByKey($profile->getId());
                        continue;
                    }
                    $deliveryType = $this->_profileHelper
                        ->getCustomerAddressType($profile->getData('shipping_address_id'));
                    $deliveryTypeName = $this->getFirstDeliveryName($profile->getId());

                    $profile->setData('delivery_type', $deliveryType);
                    $profile->setData('delivery_type_name', __($deliveryTypeName));
                    $profile->setData('next_delivery_date_format',
                        $this->_profileHelper->formatDate($profile->getNextDeliveryDate()));
                }
            }
            $result['profiles'] = $profiles->toArray();
        }

        $blockNoScription = $this->getLayout()->createBlock('Magento\Cms\Block\Block')->setBlockId('no-subscription');
        if ($blockNoScription) {
            $result['no_scription'] = $blockNoScription->toHtml();
        }

        $result['price_format'] = $this->_localeFormat->getPriceFormat(null, 'JPY');
        $result['confirm_url'] = $this->getUrl('subscriptions/profile/confirmspotproduct');

        return \Zend_Json::encode($result, JSON_FORCE_OBJECT);
    }

    public function getAllDeliveryTypes($profiles)
    {
        $result = [];
        if ($profiles) {
            foreach ($profiles as $profile) {
                $result[$profile->getId()] = $this->_profileHelper->getCustomerAddressType(
                    $profile->getData('shipping_address_id')
                );
            }
        }
        return $result;
    }

    /**
     * Get first delivery type for profile
     *
     * @param int $profileId
     *
     * @return string
     */
    public function getFirstDeliveryName($profileId)
    {
        $firstName = '';
        $deliveryTypeName = $this->_profileHelper
            ->getDeliveryTypeOfProfile($profileId);
        $names = explode(',', $deliveryTypeName);
        if ($names && count($names)) {
            $firstName = $names[0];
        }
        return $firstName;
    }

    /**
     * Checking customer login status
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isCustomerLoggedIn()
    {
        return $this->httpContext->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
    }

    public function getLoginUrl()
    {
        return $this->customerUrl->getLoginUrl();
    }

    public function isCaseUnit($displayCase)
    {
        if ($displayCase == CaseDisplay::CD_CASE_ONLY) {
            return true;
        }
        return false;
    }

    /**
     * Return KSS login Link
     *
     * @return mixed|string
     */
    public function getLoginKssUrl()
    {
        return $this->ssoUrl->getLoginUrl($this->_urlBuilder->getCurrentUrl().'?stock=popup');
    }

    public function getProductQtyJsData(){
        $_product = $this->getProduct();
        return \Zend_Json::encode([
            "productId" => $_product->getId(),
            "categoryId" => "",
            "isHanpukai" => false,
            "minQty" => $this->getMinimalQty($_product),
            "maxQty" => $_product->getExtensionAttributes()->getStockItem()->getMaxSaleQty(),
            "isDisable" => (!$_product->getIsSalable() || !$this->isAllowSpotOrder())
        ]);
    }

    public function isAllowSpotOrder()
    {
        return !($this->getProduct()->getCustomAttribute('allow_spot_order')
            && ($this->getProduct()->getCustomAttribute('allow_spot_order')->getValue() != '1'));
    }
}
