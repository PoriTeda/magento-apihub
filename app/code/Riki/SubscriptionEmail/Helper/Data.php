<?php
namespace Riki\SubscriptionEmail\Helper;

use Symfony\Component\Config\Definition\Exception\Exception;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_SUBSCRIPTION_PROFILE_EMAIL_TEMPLATE = 'subscriptioncourse/subscriptionprofile/email_template';
    const CONFIG_SUBSCRIPTION_PROFILE_EMAIL_ENABLE = 'subscriptioncourse/subscriptionprofile/enable';
    const CONFIG_SUBSCRIPTION_PROFILE_EMAIL_SENDER = 'subscriptioncourse/subscriptionprofile/sender';
    const CONFIG_SUBSCRIPTION_PROFILE_EMAIL_SEND_COPY_METHOD = 'subscriptioncourse/subscriptionprofile/send_email_copy_method';
    const CONFIG_SUBSCRIPTION_PROFILE_EMAIL_SEND_EMAIL_COPY_TO = 'subscriptioncourse/subscriptionprofile/send_email_copy_to';
    const CONFIG_SUBSCRIPTION_PROFILE_HANPUKAI_EMAIL_TEMPLATE = 'subscriptioncourse/subscriptionprofile/hanpukai_email_template';

    /* @var \Magento\Store\Model\StoreManagerInterface */
    protected $_storeManager;

    /* @var \Magento\Framework\Mail\Template\TransportBuilder */
    protected $_transportBuilder;

    /* @var \Riki\SubscriptionCourse\Model\Course */
    protected $_courseModel;

    /* @var \Magento\Checkout\Model\Session */
    protected $_checkoutSession;

    /* @var \Riki\DeliveryType\Model\DeliveryDate */
    protected $_deliveryDateModel;

    /* @var \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart\CollectionFactory */
    protected $_subscriptionProfileProductCartCollectionFactory;

    /* @var \Riki\Subscription\Model\ProductCart\ProductCart */
    protected $_subscriptionProfileProductCartModel;

    /* @var \Magento\Framework\Pricing\Helper\Data */
    protected $_helperPrice;

    /* @var \Magento\Catalog\Api\ProductRepositoryInterface */
    protected $productRepository;

    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Riki\SubscriptionCourse\Model\Course $courseModel,
        \Magento\Framework\Pricing\Helper\Data $helperPrice,
        \Riki\DeliveryType\Model\DeliveryDate $deliveryDateModel,
        \Riki\Subscription\Model\ProductCart\ResourceModel\ProductCart\CollectionFactory $subscriptionProfileProductCartCollectionFactory,
        \Riki\Subscription\Model\ProductCart\ProductCart $productCartModel
    ) {
        $this->productRepository = $productRepositoryInterface;
        $this->_subscriptionProfileProductCartModel = $productCartModel;
        $this->_subscriptionProfileProductCartCollectionFactory = $subscriptionProfileProductCartCollectionFactory;
        $this->_deliveryDateModel = $deliveryDateModel;
        $this->_helperPrice = $helperPrice;
        $this->_checkoutSession = $checkoutSession;
        $this->_storeManager = $storeManager;
        $this->_transportBuilder = $transportBuilder;
        $this->_courseModel = $courseModel;
        parent::__construct($context);
    }

    /**
     * Get Email Template from config
     */
    public function getEmailTemplate()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $template = $this->scopeConfig->getValue(self::CONFIG_SUBSCRIPTION_PROFILE_EMAIL_TEMPLATE, $storeScope);
        return $template;
    }

    public function getHanpukaiEmailTemplate()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $template = $this->scopeConfig->getValue(self::CONFIG_SUBSCRIPTION_PROFILE_HANPUKAI_EMAIL_TEMPLATE, $storeScope);
        return $template;
    }

    public function getConfig($path)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->scopeConfig->getValue($path, $storeScope);
        return $config;
    }

    public function getAllFrequency()
    {
        $allFrequency = $this->_courseModel->getFrequencyValuesForForm();
        $result = array();
        foreach ($allFrequency as $frequency) {
            $result[$frequency['value']] = $frequency['label'];
        }
        return $result;
    }

    public function getDeliveryDate()
    {
        // Get Delivery Date From front end checkout
        $deliveryDate = $this->_checkoutSession->getDeliveryDateTmp();
        if(!$deliveryDate){
            // Get Delivery date from create order backend
            $paramFromCreateOrderBackend = $this->_request->getParams();
            if (isset($paramFromCreateOrderBackend['delivery-date-chilled'])) {
                $arrDeliveryDateTimeSlotChilled[0] = $paramFromCreateOrderBackend['delivery-date-chilled'];
                if (isset($paramFromCreateOrderBackend['delivery-time-chilled'])) {
                    $arrDeliveryDateTimeSlotChilled[1] = $paramFromCreateOrderBackend['delivery-time-chilled'];
                } else {
                    $arrDeliveryDateTimeSlotChilled[1] = null;
                }
                $deliveryDate['Chilled'] = $arrDeliveryDateTimeSlotChilled;
            }

            if (isset($paramFromCreateOrderBackend['delivery-date-cosmetic'])) {
                $arrDeliveryDateTimeSlotCosmetic[0] = $paramFromCreateOrderBackend['delivery-date-cosmetic'];
                if (isset($paramFromCreateOrderBackend['delivery-time-cosmetic'])) {
                    $arrDeliveryDateTimeSlotCosmetic[1] = $paramFromCreateOrderBackend['delivery-time-cosmetic'];
                } else {
                    $arrDeliveryDateTimeSlotCosmetic[1] = null;
                }
                $deliveryDate['Cosmetic'] = $arrDeliveryDateTimeSlotCosmetic;
            }

            if (isset($paramFromCreateOrderBackend['delivery-date-cold'])) {
                $arrDeliveryDateTimeSlotCold[0] = $paramFromCreateOrderBackend['delivery-date-cold'];
                if (isset($paramFromCreateOrderBackend['delivery-time-cold'])) {
                    $arrDeliveryDateTimeSlotCold[1] = $paramFromCreateOrderBackend['delivery-time-cold'];
                } else {
                    $arrDeliveryDateTimeSlotCold[1] = null;
                }
                $deliveryDate['Cold'] = $arrDeliveryDateTimeSlotCold;
            }

            if (isset($paramFromCreateOrderBackend['delivery-date-CoolNormalDm'])) {
                $arrDeliveryDateTimeSlotCoolNormalDm[0] = $paramFromCreateOrderBackend['delivery-date-CoolNormalDm'];
                if (isset($paramFromCreateOrderBackend['delivery-time-CoolNormalDm'])) {
                    $arrDeliveryDateTimeSlotCoolNormalDm[1] = $paramFromCreateOrderBackend['delivery-time-CoolNormalDm'];
                } else {
                    $arrDeliveryDateTimeSlotCoolNormalDm[1] = null;
                }
                $deliveryDate['CoolNormalDm'] = $arrDeliveryDateTimeSlotCoolNormalDm;
            }
            if ($deliveryDate) {
                $deliveryDate['fromBackendCreateOrder'] = 1;
            } else {
                $deliveryDate = null;
            }

        }
        return $deliveryDate;
    }



    public function getFormatPrice($price) {

        return $this->_helperPrice->currency($price, true, false);
    }

    public function getProductAndProductCartIdFromSubscriptionProfileProductCart($profileId)
    {
        $result = null;
        $collection = $this->_subscriptionProfileProductCartCollectionFactory->create()->addFieldToFilter('profile_id', $profileId);
        foreach($collection as $item) {
            $result[$item->getProductId()] = $item->getCartId();
        }
        return $result;
    }

    public function getDeliveryDateAndTimeSlot($profileProductCartId)
    {
        $result = null;
        $model = $this->_subscriptionProfileProductCartModel->load($profileProductCartId);
        if ($model) {
            $result[0] = $model->getDeliveryDate();
            $result[1] = $model->getDeliveryTimeSlot();
        }
        return $result;
    }

    public function splitQuoteByDeliveryType($arrProductIdCartId)
    {
        $arrCoolNormalDm = [];
        $arrChilled = [];
        $arrCosmetic = [];
        $arrCold = [];

        foreach ($arrProductIdCartId as $productId => $cartId) {
            $item = $this->loadProductById($productId);
            if($item->getDeliveryType() == \Riki\DeliveryType\Model\Delitype::CHILLED) {

                $arrChilled[] = $item->getId();

            } else if($item->getDeliveryType() == \Riki\DeliveryType\Model\Delitype::COSMETIC) {

                $arrCosmetic[] = $item->getId();

            } else if($item->getDeliveryType() == \Riki\DeliveryType\Model\Delitype::COLD) {

                $arrCold[] = $item->getId();

            } else {

                $arrCoolNormalDm[] = $item->getId();

            }
        }
        $listDeliveryType = [];
        if(count($arrCoolNormalDm)) {
            $listDeliveryType['CoolNormalDm'] = $arrCoolNormalDm;
        }
        if(count($arrChilled)) {
            $listDeliveryType['chilled'] = $arrChilled;
        }
        if(count($arrCosmetic)) {
            $listDeliveryType['cosmetic'] = $arrCosmetic;
        }
        if(count($arrCold)) {
            $listDeliveryType['cold'] = $arrCold;
        }

        return $listDeliveryType;
    }

    public function loadProductById($productId)
    {
        return $this->productRepository->getById($productId);
    }
}