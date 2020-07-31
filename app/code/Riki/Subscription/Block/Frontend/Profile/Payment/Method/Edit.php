<?php
namespace Riki\Subscription\Block\Frontend\Profile\Payment\Method;

use Riki\DeliveryType\Model\Delitype;
use Riki\Subscription\Model\Constant;

class Edit extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileHelper;

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $simulator;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $dataObjectFactory;

    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Riki\TimeSlots\Model\TimeSlotsFactory
     */
    protected $timeSlotFactory;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    protected $imageHelper;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $customerAddressRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;
    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $deliveryDate;
    /**
     * @var \Riki\Subscription\Helper\CalculateDeliveryDate
     */
    protected $calculateDeliveryDate;
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * Edit constructor.
     *
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Api\AddressRepositoryInterface $customerAddressRepository
     * @param \Magento\Catalog\Helper\Image $imageHelper
     * @param \Riki\TimeSlots\Model\TimeSlotsFactory $timeSlotsFactory
     * @param \Riki\Subscription\Helper\Profile\Data $profileHelper
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Riki\Subscription\Helper\Order\Simulator $simulator
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Api\AddressRepositoryInterface $customerAddressRepository,
        \Magento\Catalog\Helper\Image $imageHelper,
        \Riki\TimeSlots\Model\TimeSlotsFactory $timeSlotsFactory,
        \Riki\Subscription\Helper\Profile\Data $profileHelper,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Riki\Subscription\Helper\Order\Simulator $simulator,
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\DeliveryType\Model\DeliveryDate $deliveryDate,
        \Riki\Subscription\Helper\CalculateDeliveryDate $calculateDeliveryDate,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        array $data = []
    ) {
        $this->registry = $registry;
        $this->customerAddressRepository = $customerAddressRepository;
        $this->imageHelper = $imageHelper;
        $this->timeSlotFactory = $timeSlotsFactory;
        $this->profileHelper = $profileHelper;
        $this->priceCurrency = $priceCurrency;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->simulator = $simulator;
        $this->functionCache = $functionCache;
        $this->profileRepository = $profileRepository;
        $this->deliveryDate = $deliveryDate;
        $this->calculateDeliveryDate = $calculateDeliveryDate;
        $this->productRepository = $productRepository;
        $this->timezone = $context->getLocaleDate();
        $this->courseFactory = $courseFactory;
        parent::__construct($context, $data);
    }

    /**
     * Get profile
     *
     * @return null|\Riki\Subscription\Model\Profile\Profile
     */
    public function getProfile()
    {
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->getData('profile');
        if ($profile instanceof \Riki\Subscription\Api\Data\ApiProfileInterface) {
            $profile = $this->profileRepository->get($profile);
        }
        if (!$profile instanceof \Riki\Subscription\Model\Profile\Profile) {
            return null;
        }

        return $profile;
    }

    /**
     * @return \Riki\Subscription\Model\Profile\Profile|null
     */
    public function getConfirmProfile()
    {
        return $this->getProfile();
    }

    /**
     * {@inheritdoc}
     *
     * @return \Magento\Framework\View\Element\Template
     */
    public function _beforeToHtml()
    {
        $profile = $this->getConfirmProfile();
        if (!$profile) {
            return parent::_beforeToHtml();
        }

        if ($this->getIsHanpukai()) {
            $this->registry->register(
                Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_PAYMENT_METHOD,
                $this->getRequest()->getParam('payment_method')
            );
            $this->registry->register(
                Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_CHANGE_TYPE,
                'type_1'
            ); // apply for all next delivery
            $this->registry->register(
                Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_PROFILE_ID,
                $profile->getProfileId()
            );
            $this->registry->register(
                Constant::REGISTRY_EDIT_HANPUKAI_DATA_SUBSCRIPTION_Preferred_Payment_Method,
                $this->getRequest()->getParam('preferred_payment_method')
            );
        } else {
            $requestPaymentMethod = $this->getRequest()->getParam('payment_method');
            if ($this->_session->getProfileData() == null) {
                $obj = $this->dataObjectFactory->create();
                $obj->setData($profile->getData());
                $obj->setData("course_data", $profile->getCourseData());
                $obj->setData("product_cart", $profile->getProductCartData());
                $obj->setData('profile_type', 'type_1'); // apply for all next delivery
                if ($requestPaymentMethod) {
                    $obj->setPaymentMethod($requestPaymentMethod);
                    $obj->setIsNewPaygentMethod(true);
                }
                $this->_session->setProfileData([$profile->getProfileId() => $obj]);
            } else {
                $profileData = $this->_session->getProfileData();
                $tmpProfile = $this->profileHelper->getTmpProfile($this->getProfileId());
                if ($tmpProfile) {
                    unset($profileData[$tmpProfile->getData('linked_profile_id')]);
                    $this->_session->setProfileData($profileData);
                }
                if (isset($profileData[$profile->getProfileId()])) {
                    if (!$requestPaymentMethod) {
                        unset($profileData[$profile->getProfileId()]);
                        $obj = $this->dataObjectFactory->create();
                        $obj->setData($profile->getData());
                        $obj->setData("course_data", $profile->getCourseData());
                        $obj->setData("product_cart", $profile->getProductCartData());
                        $obj->setData('profile_type', 'type_1'); // apply for all next delivery
                        $profileData[$profile->getProfileId()] = $obj;
                    } else {
                        $profileData[$profile->getProfileId()]->setPaymentMethod($requestPaymentMethod);
                        $profileData[$profile->getProfileId()]->setIsNewPaygentMethod(true);
                    }
                } else {
                    $obj = $this->dataObjectFactory->create();
                    $obj->setData($profile->getData());
                    $obj->setData("course_data", $profile->getCourseData());
                    $obj->setData("product_cart", $profile->getProductCartData());
                    $obj->setData('profile_type', 'type_1'); // apply for all next delivery
                    if ($requestPaymentMethod) {
                        $obj->setPaymentMethod($requestPaymentMethod);
                        $obj->setIsNewPaygentMethod(true);
                    }
                    $profileData[$profile->getProfileId()] = $obj;
                }
                $this->_session->setProfileData($profileData);
            }
            $this->registry->register(
                'subscription_profile',
                $this->_session->getProfileData()[$profile->getProfileId()]
            );
            $this->registry->register('subscription_profile_obj', $profile);
        }

        $courseId = $profile->hasData('course_id')
            ? $profile->getData('course_id')
            : 0;
        $frequencyId = $profile->getSubProfileFrequencyID();
        $this->registry->register(Constant::RIKI_COURSE_ID, $courseId);
        $this->registry->register(Constant::RIKI_FREQUENCY_ID, $frequencyId);

        return parent::_beforeToHtml();
    }

    /**
     * Get available payment methods
     *
     * @return array
     */
    public function getAvailablePaymentMethods()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }

        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->getProfile();
        if (!$profile instanceof \Riki\Subscription\Model\Profile\Profile) {
            return [];
        }

        $result = $profile->getListPaymentMethodAvailable();
        $this->functionCache->store($result);

        return $result;
    }

    /**
     * Get payment method of profile
     *
     * @return string
     */
    public function getProfilePaymentMethod()
    {
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->getData('profile');
        if (!$profile instanceof \Riki\Subscription\Model\Profile\Profile) {
            return '';
        }

        return $profile->getPaymentMethod();
    }

    /**
     * Get id of profile
     *
     * @return int
     */
    public function getProfileId()
    {
        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->getData('profile');
        if (!$profile instanceof \Riki\Subscription\Model\Profile\Profile) {
            return 0;
        }

        return $profile->getProfileId();
    }

    /**
     * Get selected payment method
     *
     * @return string
     */
    public function getSelectedPaymentMethod()
    {
        if ($this->functionCache->has()) {
            return $this->functionCache->load();
        }

        $method = $this->getRequest()->getParam('payment_method');
        if (!in_array($method, array_column($this->getAvailablePaymentMethods(), 'value'))) {
            $method = '';
        }

        $this->functionCache->store($method);

        return $method;
    }

    /**
     * Get submit url
     *
     * @return string
     */
    public function getSubmitUrl()
    {
        return $this->getUrl('subscriptions/profile/payment_method_edit', [
            'id' => $this->getProfileId()
        ]);
    }

    /**
     * Get simulate order
     *
     * @return \Riki\Subscription\Model\Emulator\Order|null
     */
    public function getSimulateOrder()
    {
        $profile = $this->getProfile();
        if (!$profile) {
            return null;
        }
        /** @var \Magento\Framework\DataObject $data */
        $data = $this->dataObjectFactory->create();
        $data->addData($profile->getStoredData());
        $data->setData('product_cart', $profile->getProductCartData());
        $data->setData('course_data', $profile->getCourseData());
        $data->setData('payment_method', $this->getSelectedPaymentMethod());

        $this->registry->unregister('subscription_profile_obj');
        $this->registry->unregister('subscription_profile');
        $this->registry->register('subscription_profile_obj', $profile);
        $this->registry->register('subscription_profile', $profile);

        /** @var \Riki\Subscription\Model\Emulator\Order $result */
        $result = $this->simulator->createSimulatorOrderHasData($data);
        return $result;
    }

    /**
     * Format currency
     *
     * @param $amount
     * @param bool $includeContainer
     * @param int $precision
     *
     * @return float
     */
    public function formatCurrency(
        $amount,
        $includeContainer = false,
        $precision = \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION
    ) {
        return $this->priceCurrency->format($amount, $includeContainer, $precision);
    }

    /**
     * Get list product by shipping address id and delivery type
     *
     * @return array
     */
    public function getListProductByAddressIdAndDeliveryType()
    {
        $arrResult = [];
        $productCarts = $this->profileHelper->getArrProductCart($this->getProfileId());
        foreach ($productCarts as $productId => $arrProductInfo) {
            $deliveryType = $arrProductInfo['details']->getData('delivery_type');
            $shippingAddressId = $arrProductInfo['profile']->getData('shipping_address_id');
            if ($deliveryType == Delitype::COLD || $deliveryType == Delitype::NORMAl || $deliveryType == Delitype::DM) {
                $deliveryType = Delitype::COOL_NORMAL_DM;
            }

            $arrResult[$shippingAddressId][$deliveryType][] = $arrProductInfo;
        }

        return $arrResult;
    }

    /**
     * Get address detail by address id
     *
     * @param $addressId
     *
     * @return mixed
     * @throws \Exception
     */
    public function getAddressDetail($addressId)
    {
        if ($this->functionCache->has($addressId)) {
            return $this->functionCache->load($addressId);
        }

        $customerShippingAddress = $this->customerAddressRepository->getById($addressId);

        if ($customerShippingAddress instanceof \Magento\Customer\Model\Data\Address) {
            if ($rikiNicknameObj = $customerShippingAddress->getCustomAttribute('riki_nickname')) {
                $rikiNickname = $rikiNicknameObj->getValue();
            } else {
                $rikiNickname = '';
            }

            if ($rikiFirstnameKanaObj = $customerShippingAddress->getCustomAttribute('firstnamekana')) {
                $rikiFirstnameKana = $rikiFirstnameKanaObj->getValue();
            } else {
                $rikiFirstnameKana = '';
            }

            if ($rikiLastnameKanaObj = $customerShippingAddress->getCustomAttribute('lastnamekana')) {
                $rikiLastnameKana = $rikiLastnameKanaObj->getValue();
            } else {
                $rikiLastnameKana = '';
            }

            if ($rikiTypeAddressObj = $customerShippingAddress->getCustomAttribute('riki_type_address')) {
                $rikiTypeAddress = $rikiTypeAddressObj->getValue();
            } else {
                $rikiTypeAddress = '';
            }
        } else {
            $rikiNickname
                = $customerShippingAddress ? $customerShippingAddress->getData('riki_nickname') : '';
            $rikiFirstnameKana
                = $customerShippingAddress ? $customerShippingAddress->getData('firstnamekana') : '';
            $rikiLastnameKana
                = $customerShippingAddress ? $customerShippingAddress->getData('lastnamekana') : '';
            $rikiTypeAddress
                = $customerShippingAddress ? $customerShippingAddress->getData('riki_type_address') : '';
        }
        $arrReturn['lastname'] = $customerShippingAddress->getLastname();
        $arrReturn['firstname'] = $customerShippingAddress->getFirstname();
        $arrReturn['riki_nickname'] = $rikiNickname;
        $arrReturn['riki_firstnamekana'] = $rikiFirstnameKana;
        $arrReturn['riki_lastnamekana'] = $rikiLastnameKana;
        $arrReturn['riki_type_address'] = $rikiTypeAddress;
        $arrReturn['telephone'] = $customerShippingAddress ? $customerShippingAddress->getTelephone() : '';
        $postCode = $customerShippingAddress->getPostcode();
        $region = $customerShippingAddress->getRegion() ? $customerShippingAddress->getRegion()->getRegion() : '';
        $arrReturn['formatted'] = implode(
            ' ',
            ['〒 ' . $postCode, $region, trim(implode(" ", $customerShippingAddress->getStreet()))]
        );

        $this->functionCache->store($arrReturn, $addressId);

        return $arrReturn;
    }

    /**
     * Get slot object

     * @return \Riki\TimeSlots\Model\TimeSlots
     */
    public function getTimeSlot()
    {
        if ($this->functionCache->has($this->getProfileId())) {
            return $this->functionCache->load($this->getProfileId());
        }

        $items = $this->profileHelper->getProductHaveTimeSlot($this->getProfileId())->getItems();
        $item = $items ? end($items) : null;
        $slotId = $item ? $item->getData('delivery_time_slot') : 0;
        $result = $this->timeSlotFactory->create()->load($slotId);

        $this->functionCache->store($result, $this->getProfileId());

        return $result;
    }

    /**
     * Get next delivery date
     *
     * @return string
     */
    public function getNextDeliveryDate()
    {
        return $this->getProfile()
            ? $this->getProfile()->getData('next_delivery_date')
            : date('Y-m-d');
    }

    /**
     * Get next delivery date as string
     *
     * @return string
     */
    public function getNextDeliveryDateAsString()
    {
        $nextDeliveryDate = $this->getNextDeliveryDate();
        $day = date('D', strtotime($nextDeliveryDate));
        return $nextDeliveryDate . ' (' . __($day) . ')';
    }

    /**
     * Get image url
     *
     * @param $product
     * @return string
     */
    public function getImageUrl($product)
    {
        /** @var $product \Magento\Catalog\Model\Product */
        return $this->imageHelper->init($product, 'cart_page_product_thumbnail')
            ->keepFrame(false)
            ->constrainOnly(true)
            ->resize(160, 160);
    }

    /**
     * Get is hanpukai
     *
     * @return bool
     */
    public function getIsHanpukai()
    {
        $profile = $this->getProfile();
        $courseData = $profile
            ? $profile->getCourseData()
            : [];

        return (isset($courseData['subscription_type']) && $courseData['subscription_type'] == 'hanpukai' )
            ? true
            : false;
    }

    /**
     * Get course name
     *
     * @return string
     */
    public function getCourseName()
    {
        $profile = $this->getProfile();
        if (!$profile) {
            return '';
        }

        return $profile->getCourseName();
    }

    /**
     * Get frequency text
     *
     * @return string
     */
    public function getFrequencyText()
    {
        $profile = $this->getProfile();
        if (!$profile) {
            return '';
        }

        return __($profile->getFrequencyInterval()) . ' ' . __($profile->getFrequencyUnit());
    }

    public function getAllTimeSlot()
    {
        $timeSlot = $this->deliveryDate->getListTimeSlot();
        return $timeSlot;
    }

    public function getCalculateDeliveryDate()
    {
        return $this->calculateDeliveryDate;
    }

    public function getAddressIdInProfile()
    {
        $profile = $this->getProfile();
        $productCarts = $profile->getProductCartData();
        foreach ($productCarts as $productCart) {
            return $productCart->getData('shipping_address_id');
        }
    }

    /**
     * @return array
     */
    public function getProductIdOfProfile()
    {
        $profile = $this->getProfile();
        $productCarts = $profile->getProductCartData();
        $productIds = [];
        foreach ($productCarts as $productCart) {
            $productObj = $this->productRepository->getById($productCart->getData('product_id'));
            $productIds['product'][] = [
                'instance'=>$productObj,
                'id'=>$productCart->getData('product_id'),
                'qty' =>$productCart->getData('qty')
            ];
        }
        return $productIds;
    }

    /**
     * get profile max delivery date
     *
     * @return string
     */
    public function getProfileMaxDeliveryDate()
    {
        $frequencyUnit = $this->getProfile()->getData('frequency_unit');
        $frequencyInterval = $this->getProfile()->getData('frequency_interval');
        $nextDeliveryDate = $this->getProfile()->getData('next_delivery_date');

        $maxDateTime = strtotime($frequencyInterval . " " . $frequencyUnit, strtotime($nextDeliveryDate));
        $maxDateTime = strtotime('-1 day', $maxDateTime);
        $objDate  = $this->timezone->date();
        $objDate->setTimestamp($maxDateTime);
        return $objDate->format(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT);
    }

    /**
     * Get next delivery date calculation option
     *
     * @param $courseId
     * @return string
     */
    public function getNextDeliveryDateCalculationOption($courseId)
    {
        $courseModel = $this->courseFactory->create()->load($courseId);

        if (!$courseModel) {
            return '';
        }
        return $courseModel->getData('next_delivery_date_calculation_option');
    }
}
