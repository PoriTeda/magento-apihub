<?php
namespace Riki\Subscription\Model\Profile\WebApi\LandingPage;

use Magento\Framework\Exception\NoSuchEntityException;
use Riki\Loyalty\Model\ConsumerDb\CustomerSub;
use Riki\Loyalty\Model\ConsumerDb\ShoppingPoint;
use Riki\Loyalty\Model\RewardQuote;
use Riki\Subscription\Api\WebApi\LandingPage\LandingPageProfileInterface;
use Riki\Subscription\Helper\WebApi\DeliveryDateHelper;
use Riki\Subscription\Model\Profile\Profile;

class LandingPageProfile implements LandingPageProfileInterface
{
    const CUSTOMER_NOT_OWN_PROFILE = 403;

    const PROFILE_NOT_AVAILABLE = 404;

    const UNAVAILABLE_INPUT_DATA = 400;

    const SOME_ERROR_HAPPENS = 500;

    const REDIRECT = 301;

    const BOTTOM_BANNER = 'riki-mypage-bottom-banner';

    const NAVIGATION_BANNER = 'nav_static-login';

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * @var \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory
     */
    private $profileCollectionFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    private $dateTime;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    private $_helperProfile;

    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;
    /**
     * @var \Riki\Subscription\Helper\WebApi\DeliveryDateHelper
     */
    protected $deliveryDateHelper;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory
     */
    protected $orderItemCollectionFactory;



    /* @var \Magento\Customer\Api\AddressRepositoryInterface */
    protected $_customerAddressRepository;

    /**
     * @var \Magento\Store\Model\App\Emulation
     */
    protected $appEmulation;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Payment\Helper\Data
     */
    protected $paymentHelper;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Riki\Subscription\Model\Frequency\Frequency
     */
    protected $_frequencyModel;

    /**
     * @var \Magento\Framework\View\Element\BlockFactory
     */
    protected $blockFactory;

    /**
     * @var \Riki\Subscription\Model\Paygent
     */
    protected $paygentModel;

    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Cms\Model\BlockFactory
     */
    private $cmsBlockFactory;

    /**
     * @var ResourceModel\Block
     */
    private $blockResource;

    /**
     * @var \Magento\Cms\Model\Template\FilterProvider
     */
    protected $filterProvider;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\StockPoint\Api\BuildStockPointPostDataInterface
     */
    protected $buildStockPointPostData;
    /**
     * @var \Riki\Subscription\Api\WebApi\ProfileRepositoryInterface
     */
    private $profileWebRepository;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerModelFactory;

    /**
     * @var \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint
     */
    protected $shoppingPoint;

    /**
     * @var \Riki\Loyalty\Model\ResourceModel\RewardFactory
     */
    protected $rewardResourceFactory;

    /**
     * @var \Riki\Customer\Model\CustomerRepository
     */
    protected $consumerCustomerRepository;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var CustomerSub
     */
    protected $customerSub;

    /**
     * Translator
     *
     * @var \Magento\Framework\TranslateInterface
     */
    protected $translator;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var \Riki\Loyalty\Model\RewardQuoteFactory
     */
    protected $rewardQuoteFactory;

    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $rewardManagement;

    /**
     * @var \Riki\Loyalty\Api\CheckoutRewardPointInterface
     */
    protected $checkoutRewardPoint;

    /**
     * @var \Riki\SerialCode\Model\ResourceModel\SerialCode\CollectionFactory
     */
    protected $serialCodeCollectionFactory;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subscriptionOrderHelper;


    public function __construct(
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory $profileCollectionFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\Subscription\Helper\Profile\Data $_helperProfile,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Helper\WebApi\DeliveryDateHelper $deliveryDateHelper,
        \Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory $orderItemCollectionFactory,
        \Magento\Store\Model\App\Emulation $appEmulation,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Payment\Helper\Data $paymentHelper,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Model\Frequency\Frequency $_frequencyModel,
        \Magento\Framework\View\Element\BlockFactory $blockFactory,
        \Riki\Subscription\Model\Paygent $paygentModel,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Cms\Model\BlockFactory $cmsBlockFactory,
        \Magento\Cms\Model\ResourceModel\Block $blockResource,
        \Magento\Cms\Model\Template\FilterProvider $filterProvider,
        \Psr\Log\LoggerInterface $logger,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        \Riki\Subscription\Api\WebApi\ProfileRepositoryInterface $profileWebRepository,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Customer\Model\CustomerFactory $customerModelFactory,
        \Riki\Loyalty\Model\ConsumerDb\ShoppingPoint $shoppingPoint,
        \Riki\Loyalty\Model\ResourceModel\RewardFactory $rewardResourceFactory,
        \Riki\Customer\Model\CustomerRepository $consumerCustomerRepository,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Riki\Loyalty\Model\ConsumerDb\CustomerSub $customerSub,
        \Magento\Framework\TranslateInterface $translator,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository,
        \Riki\Loyalty\Model\RewardQuoteFactory $rewardQuoteFactory,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        \Riki\Loyalty\Api\CheckoutRewardPointInterface $checkoutRewardPoint,
        \Riki\SerialCode\Model\ResourceModel\SerialCode\CollectionFactory $serialCodeCollectionFactory,
        \Riki\Subscription\Helper\Order $subscriptionOrderHelper
    )
    {
        $this->profileRepository = $profileRepository;
        $this->profileCollectionFactory = $profileCollectionFactory;
        $this->dateTime = $dateTime;
        $this->_helperProfile = $_helperProfile;
        $this->profileFactory = $profileFactory;
        $this->deliveryDateHelper = $deliveryDateHelper;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->appEmulation = $appEmulation;
        $this->storeManager = $storeManager;
        $this->paymentHelper = $paymentHelper;
        $this->courseFactory = $courseFactory;
        $this->_frequencyModel = $_frequencyModel;
        $this->blockFactory = $blockFactory;
        $this->paygentModel = $paygentModel;
        $this->urlBuilder = $urlBuilder;
        $this->cmsBlockFactory = $cmsBlockFactory;
        $this->blockResource = $blockResource;
        $this->filterProvider = $filterProvider;
        $this->logger = $logger;
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->profileWebRepository = $profileWebRepository;
        $this->productRepository = $productRepository;
        $this->customerModelFactory = $customerModelFactory;
        $this->shoppingPoint = $shoppingPoint;
        $this->rewardResourceFactory = $rewardResourceFactory;
        $this->consumerCustomerRepository = $consumerCustomerRepository;
        $this->scopeConfig = $scopeConfig;
        $this->customerSub = $customerSub;
        $this->translator = $translator;
        $this->quoteRepository = $quoteRepository;
        $this->rewardQuoteFactory = $rewardQuoteFactory;
        $this->rewardManagement = $rewardManagement;
        $this->checkoutRewardPoint = $checkoutRewardPoint;
        $this->serialCodeCollectionFactory = $serialCodeCollectionFactory;
        $this->subscriptionOrderHelper = $subscriptionOrderHelper;
    }

    /**
     * @param int|null $profileId
     * @param int $customerId
     * @return array|mixed
     */
    public function getLandingPageProfile($profileId, $customerId){
        try {
            $this->startEmulation();
            $response = $this->checkProfileAvailable($profileId, $customerId);
            if(!$response['available']){
                return $response['content'];
            }
            return $this->generateResponse($this->loadCurrentProfileInfo($profileId));
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    /**
     * @param int $customerId
     * @return array|mixed
     */
    public function getLandingPageProfileList($customerId)
    {
        try {
            $this->startEmulation();
            return $this->generateResponse($this->loadProfileList($customerId));
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    /**
     * @param int $customerId
     * @param string $deliveryDate
     * @param int|string $deliveryTime
     * @param int $profileId
     * @return array|mixed
     */
    public function setLandingPageDeliveryDate($customerId, $deliveryDate, $deliveryTime, $profileId)
    {
        try {
            $this->startEmulation();
            $response = $this->checkProfileAvailable($profileId, $customerId);
            if(!$response['available']){
                return $response['content'];
            }
            if(!$this->deliveryDateHelper->verifySetDeliveryDate($profileId, $deliveryDate, $deliveryTime)){
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA);
            }
            $nextOrderDate = $this->deliveryDateHelper->deliveryDateChange($profileId, $deliveryDate, $deliveryTime);
            $tempProfileLink = $this->_helperProfile->getTmpProfile($profileId);
            if ($tempProfileLink) {
                $tempProfileId = $tempProfileLink->getLinkedProfileId();
                $tempProfile = $this->profileFactory->create()->load($tempProfileId);
                if ($tempProfile->getId()) {
                    $this->deliveryDateHelper->deliveryDateChange($tempProfile->getId(), $deliveryDate, $deliveryTime);
                }
            }
            return $this->generateResponse(['deadline' => date("Y-m-d", strtotime($nextOrderDate) - 86400)]);
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    /**
     * @param int $profileId
     * @param int $customerId
     * @return mixed|void
     */
    public function getLandingPageFrequency($profileId, $customerId)
    {
        try {
            $this->startEmulation();
            $response = $this->checkProfileAvailable($profileId, $customerId);
            if(!$response['available']){
                return $response['content'];
            }
            $profile = $this->profileRepository->get($profileId);
            $frequencySelected = null;
            $frequencyList =  $this->getFrequencyList($profileId);
            foreach($frequencyList as $frequency){
                if($frequency['label'] == __($profile->getFrequencyInterval()) . __($profile->getFrequencyUnit())){
                    $frequencySelected = $frequency['frequency_id'];
                }
            }
            return $this->generateResponse([
                'frequency_list' => $frequencyList,
                'frequency_selected' => $frequencySelected
            ]);
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    /**
     * @param int $customerId
     * @param int $frequencyId
     * @param int $profileId
     * @return mixed|void
     */
    public function setLandingPageFrequency($customerId, $frequencyId, $profileId)
    {
        try {
            $this->startEmulation();
            $response = $this->checkProfileAvailable($profileId, $customerId);
            if(!$response['available']){
                return $response['content'];
            }

            $frequencyList =  $this->getFrequencyList($profileId);
            $available = false;
            foreach($frequencyList as $frequency){
                if($frequency['frequency_id'] == $frequencyId){
                    $available = true;
                    break;
                }
            }
            if(!$available){
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA);
            }
            $frequencyModel = $this->_frequencyModel->load($frequencyId);
            // validate restriction
            $profileCache = $this->deliveryDateHelper->getProfileEntity($profileId);
            $profileCache->setData('frequency_unit', $frequencyModel->getData('frequency_unit'));
            $profileCache->setData('frequency_interval', $frequencyModel->getData('frequency_interval'));
            // validate amount restriction
            $subscriptionCourse = $this->subscriptionOrderHelper->loadCourse($profileCache->getData('course_id'));
            $validateResult = $this->subscriptionOrderHelper->validateSimulateOrderAmountRestriction(
                $subscriptionCourse,
                $profileCache
            );
            if (!$validateResult['status']) {
                $this->deliveryDateHelper->removeProfileCache($profileId);
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA, null, $validateResult['message']);
            }
            $this->_helperProfile->UpdateFrequency(
                $profileId, $frequencyModel->getData('frequency_unit'), $frequencyModel->getData('frequency_interval'));
            $this->deliveryDateHelper->removeProfileCache($profileId);
            return $this->generateResponse(null);
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    /**
     * @param int $profileId
     * @param int $customerId
     * @return array|mixed
     */
    public function getLandingPagePaymentMethod($profileId, $customerId)
    {
        try {
            $this->startEmulation();
            $response = $this->checkProfileAvailable($profileId, $customerId);
            if(!$response['available']){
                return $response['content'];
            }
            $profile = $this->profileFactory->create()->load($profileId);
            $response = $this->getAvailablePaymentMethod($profile, $customerId);
            return $this->generateResponse($response);
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    /**
     * @param int $customerId
     * @param string $paymentMethod
     * @param string $redirectUrl
     * @param int $profileId
     * @return mixed
     */
    public function setLandingPagePaymentMethod($customerId, $paymentMethod, $redirectUrl, $profileId){
        try {
            $this->startEmulation();
            $response = $this->checkProfileAvailable($profileId, $customerId);
            if(!$response['available']){
                return $response['content'];
            }
            $profile = $this->profileFactory->create()->load($profileId);
            $availablePaymentMethodList = $this->getAvailablePaymentMethod($profile, $customerId)['payment_method_list'];
            $available = false;
            foreach($availablePaymentMethodList as $availablePaymentMethod){
                if(isset($availablePaymentMethod['disabled'])) {
                    if ($paymentMethod === $availablePaymentMethod['value'] && !$availablePaymentMethod['disabled']) {
                        $available = true;
                        break;
                    }
                } else {
                    $available = true;
                }
            }
            if(!$available){
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA);
            }
            $shippingAddress = null;
            $deliveryType = null;
            foreach($profile->getProductCart() as $cart) {
                $deliveryType = $this->productRepository->getById($cart->getData('product_id'))->getData('delivery_type');
                $shippingAddress = $cart->getData('shipping_address_id');
                if($deliveryType && $shippingAddress) {
                    break;
                }
            }
            // validate address & payment method
            $profileCache = $this->deliveryDateHelper->getProfileEntity($profileId);
            $profileCache->setData('payment_method', $paymentMethod  == 'new_paygent' ? 'paygent' : $paymentMethod);
            $arrAddress = [$shippingAddress => [$deliveryType => $shippingAddress]];
            $validateCustomerAddress = $this->deliveryDateHelper->validateCustomerAddress($profileCache, $arrAddress);
            if(!$validateCustomerAddress['status']){
                $this->deliveryDateHelper->removeProfileCache($profileId);
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA, null, 'お客様ご本人以外をお届け先に指定された場合、お支払い方法に代金引換は指定できません。');
            }
            // validate amount restriction
            $subscriptionCourse = $this->subscriptionOrderHelper->loadCourse($profileCache->getData('course_id'));
            $validateResult = $this->subscriptionOrderHelper->validateSimulateOrderAmountRestriction(
                $subscriptionCourse,
                $profileCache
            );
            if (!$validateResult['status']) {
                $this->deliveryDateHelper->removeProfileCache($profileId);
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA, null, $validateResult['message']);
            }
            $tradingId = $profile->getTradingId();
            $redirectPath = null;
            if (($paymentMethod == \Bluecom\Paygent\Model\Paygent::CODE && !$tradingId) ||
                ($paymentMethod == 'new_paygent')
            ) {
                try {
                    $redirectPath = $this->paygentModel->validateCardApi($profileId, $redirectUrl);
                } catch(\Exception $e){
                    return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
                }
            }
            $profile->setPaymentMethod($paymentMethod  == 'new_paygent' ? 'paygent' : $paymentMethod);
            $profile->save();
            $profile = $this->profileFactory->create()->load($profileId);
            $versionId = $this->_helperProfile->checkProfileHaveVersion($profile->getId());
            if ($versionId) {
                $versionProfile = $this->profileFactory->create()->load($versionId);
                if ($versionProfile->getId()) {
                    $versionProfile->setPaymentMethod($paymentMethod  == 'new_paygent' ? 'paygent' : $paymentMethod);
                    $versionProfile->save();
                }
            }
            $tempProfileLink = $this->_helperProfile->getTmpProfile($profile->getId());
            if ($tempProfileLink) {
                $tempProfileId = $tempProfileLink->getLinkedProfileId();
                $tempProfile = $this->profileFactory->create()->load($tempProfileId);
                if ($tempProfile->getId()) {
                    $tempProfile->setPaymentMethod($paymentMethod  == 'new_paygent' ? 'paygent' : $paymentMethod);
                    $tempProfile->setRandomTrading($profile->getRandomTrading());
                    $tempProfile->save();
                }
            }
            $this->deliveryDateHelper->removeProfileCache($profileId);
            if ($redirectPath) {
                return $this->generateResponse(null, self::REDIRECT, null, $redirectPath);
            }
            return $this->generateResponse(null);
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    /**
     * @param int $profileId
     * @param string $redirectUrl
     * @param int $customerId
     * @return mixed
     */
    public function getLandingPageShippingAddress($profileId, $redirectUrl, $customerId)
    {
        try {
            $this->startEmulation();
            $result = $this->checkProfileAvailable($profileId, $customerId);
            if(!$result['available']){
                return $result['content'];
            }
            $shippingAddressList = $this->deliveryDateHelper->getAllAddress($customerId);
            foreach($shippingAddressList as $shippingAddress){
                $response['address_list'][] = [
                    'address_id' => $shippingAddress['id'],
                    'address_label' => $shippingAddress['riki_nickname'],
                    'address_name' => $shippingAddress['riki_lastname'] . $shippingAddress['riki_firstname'],
                    'address_text' => $shippingAddress['riki_address_text'],
                    'address_telephone' => $shippingAddress['telephone'],
                    'address_edit_url' => $shippingAddress['riki_edit_url']
                ];
            }
            $this->deliveryDateHelper->getModifiableOrder($profileId);
            $shippingStatus = $this->deliveryDateHelper->getDataFromCache($profileId, DeliveryDateHelper::TYPE_SHIPPING_ADDRESS);
            $response['selected_shipping_address'] = $shippingStatus['selected_shipping_address'];
            $response['address_create_new_url'] = $this->deliveryDateHelper->getNewAddressUrl();
            $response['is_disable'] = $shippingStatus['is_disable'];
            if($shippingStatus['stockpoint_option']['stockpoint_address']['address_id'] == null && $shippingStatus['stockpoint_option']['stockpoint_exist']){
                foreach($response['address_list'] as $address){
                    if($address['address_id'] == $shippingStatus['selected_shipping_address']){
                        $shippingStatus['stockpoint_option']['stockpoint_address']['address_id'] = $address['address_id'];
                        $shippingStatus['stockpoint_option']['stockpoint_address']['address_name'] = $address['address_name'];
                        $shippingStatus['stockpoint_option']['stockpoint_address']['address_text'] = $address['address_text'];
                        $shippingStatus['stockpoint_option']['stockpoint_address']['address_telephone'] = $address['address_telephone'];
                        break;
                    }
                }
            }
            $response['stockpoint_option'] = $shippingStatus['stockpoint_option'];
            $profileCache = $this->deliveryDateHelper->getProfileEntity($profileId);
            $response['stockpoint_option']['stockpoint_delivery_type'] = $profileCache->getData('stock_point_delivery_type');
            if($response['stockpoint_option']['type_show_block'] !== null){
                switch($response['stockpoint_option']['type_show_block']){
                    case 1:
                        $blockIdentifier = 'stock_point_delivery_explanation';
                        break;
                    case 2:
                        $blockIdentifier = 'stock_point_delivery_explanation_not_allowed';
                        break;
                    case 3:
                        $blockIdentifier = 'stock_point_delivery_explanation_oos';
                        break;
                    default:
                        $blockIdentifier = null;
                        break;
                }
                if($blockIdentifier) {
                    $block = $this->getBlockCms($blockIdentifier, $this->storeManager->getStore()->getId());
                    if (!is_object($block)) {
                        $block = $this->getBlockCms($blockIdentifier, 0);
                    }

                    $blockContent = '';
                    if (is_object($block)) {
                        if ($block->isActive()) {
                            $blockContent = trim($this->filterProvider->getPageFilter()->filter($block->getContent()));
                        }
                    }
                    $response['stockpoint_option']['stockpoint_block'] = $blockContent;
                } else {
                    $response['stockpoint_option']['stockpoint_block'] = null;
                }
            } else {
                $response['stockpoint_option']['stockpoint_block'] = null;
            }
            unset($response['stockpoint_option']['type_show_block']);
            $response['course_name'] = $profileCache->getData('course_name');
            $response['order_times'] = (int)$profileCache->getData('order_times') + 1;
            return $this->generateResponse($response);
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    public function getStockPoint($profileId, $redirectUrl, $customerId)
    {
        try {
            $this->startEmulation();
            $result = $this->checkProfileAvailable($profileId, $customerId);
            if (!$result['available']) {
                return $result['content'];
            }
            $this->deliveryDateHelper->getModifiableOrder($profileId);
            $shippingStatus = $this->deliveryDateHelper->getDataFromCache($profileId, DeliveryDateHelper::TYPE_SHIPPING_ADDRESS);
            $response['stockpoint_option']['reqdata'] = $this->deliveryDateHelper->getStockPointReqData($profileId, (int)$shippingStatus['selected_shipping_address'], base64_decode(urldecode($redirectUrl)));
            return $this->generateResponse($response);
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    /**
     * @param int $customerId
     * @param int|string $shippingAddress
     * @param bool $isStockpoint
     * @param int $profileId
     * @return array|mixed
     */
    public function setLandingPageShippingAddress($customerId, $shippingAddress, $isStockpoint, $profileId)
    {
        try {
            $this->startEmulation();
            $result = $this->checkProfileAvailable($profileId, $customerId);
            if(!$result['available']){
                return $result['content'];
            }
            $profile = $this->profileFactory->create()->load($profileId);
            $profileCache = $this->deliveryDateHelper->getProfileEntity($profileId);
            $available = false;
            $shippingStatus = $this->deliveryDateHelper->getDataFromCache($profileId, DeliveryDateHelper::TYPE_SHIPPING_ADDRESS);
            $courseSettings = $this->deliveryDateHelper->getCourseSetting($profileCache->getData("course_id"));
            if(!$courseSettings['is_allow_change_address']){
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA);
            }
            if($isStockpoint){
                if(!$shippingStatus){
                    // does not have cache for some reason
                    $this->deliveryDateHelper->getModifiableOrder($profileId);
                    $shippingStatus = $this->deliveryDateHelper->getDataFromCache($profileId, DeliveryDateHelper::TYPE_SHIPPING_ADDRESS);
                    if($shippingStatus['stockpoint_option']['stockpoint_address']['address_id'] == $shippingAddress){
                        $available = true;
                    }
                }
            }
            if(!$available) {
                $shippingAddressList = $this->deliveryDateHelper->getAllAddress($customerId);
                foreach ($shippingAddressList as $address) {
                    if ($address['id'] == $shippingAddress) {
                        $available = true;
                        break;
                    }
                }
            }
            if(!$available){
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA);
            }
            if($isStockpoint){
                $params = $this->deliveryDateHelper->getDataFromCache($profileId, DeliveryDateHelper::TYPE_STOCKPOINT);
                if(!$params){
                    //case call api save current stockpoint data without modifying
                    $validateStockPoint = $this->deliveryDateHelper->validateStockPoint($profileCache);
                } else {
                    $profileCache->setData('riki_stock_point_nonce', $this->deliveryDateHelper->getDataFromCache($profileId, DeliveryDateHelper::TYPE_NONCE));
                    $validateStockPoint = $this->buildStockPointPostData->validateRequestStockPoint($params, $profileCache);

                    $receiveData = json_decode(base64_decode($params['reqdata']), true);
                    if (isset($receiveData['data']) && isset($receiveData['sig'])) {
                        $receiveData = json_decode(base64_decode($receiveData['data']), true);
                    }
                    $profileCache->setData("frequency_unit", $receiveData["frequency_unit"]);
                    $profileCache->setData("frequency_interval", $receiveData["frequency_interval"]);
                    $profileCache->setData("next_delivery_date", $receiveData["next_delivery_date"]);
                    $profileCache->setData("next_order_date", $receiveData["next_order_date"]);
                    foreach($profileCache->getData('product_cart') as $cart){
                        $cart->setData("stock_point_discount_rate", $receiveData["current_discount_rate"]);
                        $cart->setData("delivery_date", $receiveData["next_delivery_date"]);
                        $cart->setData('delivery_time_slot', $receiveData["delivery_time"]);
                    }
                }
                if(!$validateStockPoint['status']){
                    return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA, null, $validateStockPoint['message']);
                }
                $profileCache = $this->deliveryDateHelper->processProfileStockPoint($profileCache, $profileId);
            } else{
                $hasBucketId = $profileCache->getData('stock_point_profile_bucket_id');
                if($hasBucketId) {
                    $profileCache->setData("stock_point_profile_bucket_id", null)
                        ->setData("stock_point_delivery_type", null)
                        ->setData("stock_point_delivery_information", null)
                        ->setData("stock_point_data", null)
                        ->setData("riki_stock_point_id", null)
                        ->setData("is_delete_stock_point", true)
                        ->setData("delete_profile_has_bucket_id", $hasBucketId);

                    $addressBeforeChange = $profileCache->getData("riki_shipping_address_before_change");
                    foreach ($profileCache['product_cart'] as $productId => $product) {
                        $profileCache['product_cart'][$productId]->setData(
                            'stock_point_discount_rate',
                            0
                        );
                        if ($addressBeforeChange) {
                            $profileCache['product_cart'][$productId]->setData(
                                'shipping_address_id',
                                $addressBeforeChange
                            );
                        }
                    }
                }
            }

            $deliveryType = null;
            foreach($profile->getProductCart() as $cart) {
                $deliveryType = $this->productRepository->getById($cart->getData('product_id'))->getData('delivery_type');
                if($deliveryType) {
                    break;
                }
            }
            $arrAddress = $profileCache->getData('address');
            if (is_null($arrAddress)) {
                $arrAddress = [$shippingAddress => [$deliveryType => $shippingAddress]];
            }

            $validateCustomerAddress = $this->deliveryDateHelper->validateCustomerAddress($profileCache, $arrAddress);
            if(!$validateCustomerAddress['status']){
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA, null, 'お客様ご本人以外をお届け先に指定された場合、お支払い方法に代金引換は指定できません。');
            }
            // save shipping address
            foreach($profileCache->getData('product_cart') as $cart){
                $cart->setData('shipping_address_id', (int)$shippingAddress);
            }

            $profileType = $profileCache->getData('profile_type');
            $profileCache->setData('trading_id', $profile->getTradingId());
            // validate amount restriction
            $subscriptionCourse = $this->subscriptionOrderHelper->loadCourse($profileCache->getData('course_id'));
            $validateResult = $this->subscriptionOrderHelper->validateSimulateOrderAmountRestriction(
                $subscriptionCourse,
                $profileCache
            );
            if (!$validateResult['status']) {
                $this->deliveryDateHelper->removeProfileCache($profileId);
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA, null, $validateResult['message']);
            }
            $this->profileWebRepository->save($profileCache, $profileType, $arrAddress, 'FO');
            // resave verification info
            $shippingStatus['selected_shipping_address'] = $shippingAddress;
            $this->deliveryDateHelper->saveVerificationDataToCache($profileId, null, DeliveryDateHelper::TYPE_SHIPPING_ADDRESS, $shippingStatus);
            $this->deliveryDateHelper->removeDataFromCache($profileId, DeliveryDateHelper::TYPE_STOCKPOINT);

            // remove profile from cache
            $this->deliveryDateHelper->removeProfileCache($profileId);
            return $this->generateResponse(null);

        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    /**
     * @param int $customerId
     * @return mixed|void
     */
    public function getLandingPageProfileListAll($customerId)
    {
        try {
            $this->startEmulation();
            $profileList = $this->loadProfileList($customerId);
            $response = $this->loadCurrentProfileInfoNoSimulate($profileList, $customerId);
            return $this->generateResponse($response);
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    public function getLandingPageProfileDetail($profileId, $customerId)
    {
        try {
            $this->startEmulation();
            $response = $this->checkProfileAvailable($profileId, $customerId);
            if(!$response['available']){
                return $response['content'];
            }
            return $this->generateResponse($this->loadCurrentProfileInfoNoSimulate($profileId, $customerId));
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    /**
     * @param $identifier
     * @param $storeId
     *
     * @return null
     */
    protected function getBlockCms($identifier, $storeId){
        $block = $this->cmsBlockFactory->create();
        $block->setStoreId($storeId);
        $this->blockResource->load($block, $identifier, \Magento\Cms\Api\Data\BlockInterface::IDENTIFIER);

        if(!is_object($block)){
            return null;
        }

        if (!$block->getId()) {
            return null;
        }

        return $block;
    }

    /**
     * @param int $customerId
     *
     * @return array|mixed
     */
    public function getPromotionBanner($customerId)
    {
        try {
            $this->startEmulation();
            $topBannerIdentifier = $this->deliveryDateHelper->getSubscriberBlock($customerId);
            $topBanner = $this->getCMSBlockContent($topBannerIdentifier);

            $bottomBannerIdentifier = self::BOTTOM_BANNER;
            $bottomBanner = $this->getCMSBlockContent($bottomBannerIdentifier);

            return $this->generateResponse(['top_banner' => $topBanner, 'bottom_banner' => $bottomBanner]);
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    /**
     *
     * @return array|mixed
     */
    public function getNavigationBanner()
    {
        try {
            $this->startEmulation();

            $navigationBannerIdentifier = self::NAVIGATION_BANNER;
            $navigationBanner = $this->getCMSBlockContent($navigationBannerIdentifier);

            return $this->generateResponse(['navigation_banner' => $navigationBanner]);
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    protected function getCMSBlockContent($blockIdentifier){
        $block = $this->getBlockCms($blockIdentifier, $this->storeManager->getStore()->getId());
        if(!is_object($block)){
            $block = $this->getBlockCms($blockIdentifier, 0);
        }

        $blockContent = '';
        if(is_object($block)){
            if($block->isActive()){
                $blockContent = $this->filterProvider->getPageFilter()->filter($block->getContent());
            }
        }
        return $blockContent;
    }

    /**
     * @param int $customerId
     * @return mixed|void
     */
    public function getPointAndCoin($customerId)
    {
        try {
            $this->startEmulation();
            $customerCode = $this->customerModelFactory->create()->load($customerId)->getData('consumer_db_id');

            // point content
            $pointResult = $this->shoppingPoint->getPoint($customerCode, ShoppingPoint::TYPE_POINT);
            $balance = 0;
            if (!$pointResult['error']) {
                $balance = $pointResult['return']['REST_POINT'];
            }
            $response['point_balance'] = $balance;
            $response['point_tentative'] = $this->rewardResourceFactory->create()->customerTentativePoint($customerCode);
            $consumerData = $this->consumerCustomerRepository->prepareInfoSubCustomer($customerCode);
            if (isset($consumerData['USE_POINT_TYPE'])) {
                $response['use_point_type'] = $consumerData['USE_POINT_TYPE'];
            } else {
                $response['use_point_type'] = null;
            }

            if (isset($consumerData['USE_POINT_AMOUNT'])) {
                $response['use_point_amount'] = $consumerData['USE_POINT_AMOUNT'];
            } else {
                $response['use_point_amount'] = 0;
            }
            $response['point_history_url'] = $this->urlBuilder->getUrl('loyalty/reward');
            $response['point_expired_url'] = $this->urlBuilder->getUrl('loyalty/reward/expired');
            $response['point_about_url'] = $this->scopeConfig->getValue('customerksslink/kss_link_edit_customer/kss_about_nsp',
                \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE);

            // coin content
            $coinResult = $this->shoppingPoint->getPoint($customerCode, ShoppingPoint::TYPE_COIN);
            $coin = 0;
            if (!$coinResult['error']) {
                $coin = $coinResult['return']['REST_POINT'];
            }
            $response['coin_balance'] = $coin;
            $response['nestle_coin_logo'] = $this->blockFactory->createBlock("\Magento\Framework\View\Element\Template")->getViewFileUrl('images/logo-nestle.jpg');
            $response['nestle_coin_content'] = __("Nestle amuse has a lot of content for you to get nestle coints. These coins can be easily used to apply for campaigns in Nestle Amuse.");
            $block = $this->getBlockCms('about_coins', $this->storeManager->getStore()->getId());
            if(!is_object($block)){
                $block = $this->getBlockCms('about_coins', 0);
            }
            $blockContent = null;
            if(is_object($block)){
                if($block->isActive()){
                    $blockContent = trim($this->filterProvider->getPageFilter()->filter($block->getContent()));
                }
            }
            $doc = new \DOMDocument();
            $doc->loadHTML($blockContent);
            foreach($doc->getElementsByTagName('a') as $node){
                $response['nestle_coin_link'][] = [
                    'link' => $node->getAttribute("href"),
                    'text' => utf8_decode($node->nodeValue)
                ];
            }

            return $this->generateResponse($response);
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    /**
     * @param int $customerId
     * @param int $usePointType
     * @param int $usePointAmount
     * @return array|mixed
     */
    public function setPoint($customerId, $usePointType, $usePointAmount)
    {
        try {
            $this->startEmulation();
            $customerCode = $this->customerModelFactory->create()->load($customerId)->getData('consumer_db_id');
            $updateData = [
                CustomerSub::USE_POINT_TYPE => $usePointType
            ];
            if(!in_array($usePointType, [
                RewardQuote::USER_DO_NOT_USE_POINT,
                RewardQuote::USER_USE_ALL_POINT,
                RewardQuote::USER_USE_SPECIFIED_POINT
            ])){
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA);
            }
            if ($usePointType == RewardQuote::USER_USE_SPECIFIED_POINT) {
                if (is_int($usePointAmount)) {
                    $updateData[CustomerSub::USE_POINT_AMOUNT] = $usePointAmount;
                } else {
                    return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA, null, __('Please enter the Arabic numeral bigger than 1'));
                }

                $pointResult = $this->shoppingPoint->getPoint($customerCode, ShoppingPoint::TYPE_POINT);
                $balance = 0;
                if (!$pointResult['error']) {
                    $balance = $pointResult['return']['REST_POINT'];
                }
                if($usePointAmount > $balance){
                    return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA, null, str_replace('%s', $balance,__("The specified use point exceeds the \"%s\"")));
                }
            }
            $apiResponse = $this->customerSub->setCustomerSub($customerCode, $updateData);
            if ($apiResponse['error']) {
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA, null, __('Error while saving data.'));
            } else {
                //redeem active cart
                $sharedStoreIds = [$this->storeManager->getStore()->getId()];
                try {
                    $quote = $this->quoteRepository->getActiveForCustomer($customerId, $sharedStoreIds);
                } catch(NoSuchEntityException $e){
                    return $this->generateResponse();
                }

                $cartId = $quote->getId();
                $rewardQuote = $this->rewardQuoteFactory->create()->load($cartId, 'quote_id');
                if (!$rewardQuote->getId()) {
                    return $this->generateResponse();
                }
                $userSetting = $this->rewardManagement->getRewardUserSetting($customerCode);
                $this->checkoutRewardPoint->applyRewardPoint(
                    $cartId,
                    $userSetting['use_point_amount'],
                    $userSetting['use_point_type']
                );
            }
            return $this->generateResponse();
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    /**
     * @param int $customerId
     * @param string $serialCode
     * @return array|mixed
     */
    public function applySerialCode($customerId, $serialCode)
    {
        try {
            $this->startEmulation();
            if(!mb_check_encoding($serialCode, 'ASCII')){
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA, null, __('Please enter Serial Code in half-byte characters.'));
            }
            if(!ctype_alnum($serialCode)){
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA, null, __('The number is invalid. Please re-enter the correct number in the box.'));
            }
            $customer = $this->customerModelFactory->create()->load($customerId);
            /** @var  \Riki\SerialCode\Model\ResourceModel\SerialCode\Collection $collection */
            $collection = $this->serialCodeCollectionFactory->create();
            $collection->setPageSize(1);
            $collection->getSelect()->where(
                'serial_code = ?', $serialCode
            );
            if (!$collection->getSize()) {
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA, null, __('This Serial code/Lucky number is invalid.'));
            }
            /** @var \Riki\SerialCode\Model\SerialCode $serialCode */
            $serialCode = $collection->getFirstItem();
            $response = $serialCode->applySerialCode($customer, $this->storeManager->getStore()->getWebsiteId());
            if($response['err']){
                return $this->generateResponse(null, self::UNAVAILABLE_INPUT_DATA, null, $response['msg']);
            }
            return $this->generateResponse(null, null, null, "シリアルコードの登録が完了いたしました");
        } catch(\Exception $e){
            return $this->generateResponse(null, self::SOME_ERROR_HAPPENS, $e);
        }
    }

    protected function getAvailablePaymentMethod($profile, $customerId){
        $paymentMethodList = [];
        $profileCache = $this->deliveryDateHelper->getProfileEntity($profile->getProfileId());
        $courseSetting = $this->deliveryDateHelper->getCourseSetting($profile->getCourseId());
        $isAllowChangePaymentMethod = $courseSetting['is_allow_change_payment_method'];
        $currentPaymentMethod = $profile->getData("payment_method");
        $originProfileData = $this->deliveryDateHelper->loadOriginData($profile->getProfileId());
        $response = ['payment_method_list' => [], 'default_payment_method' => null];
        foreach($profile->getListPaymentMethodAvailable() as $paymentMethod){
            if($paymentMethod['value'] != 'paygent'){
                if($this->deliveryDateHelper->checkStockPoint($profile->getProfileId())){
                    $paymentMethod['disabled'] = true;
                }
                $paymentMethod['label'] = __($paymentMethod['label']);
                if($currentPaymentMethod == $paymentMethod['value']){
                    $response['default_payment_method'] = $paymentMethod['value'];
                }
            } else{
                $lastUsedCC = $this->deliveryDateHelper->getCcLastUsedDate($customerId, $profile->getProfileId());
                if($lastUsedCC != false || $originProfileData->getData('payment_method') == $paymentMethod['value']){
                    $lastUsedCcInfo['value'] = $paymentMethod['value'];
                    $lastUsedCcInfo['label'] = 'クレジットカード（前回使用）';
                    $lastUsedCcInfo['params']['price'] = '0.00';
                    $lastUsedCcInfo['description']['message'][] = '前回使用したクレジットカードでお支払いの方はこちら';
                    $lastUsedCcInfo['description']['message'][] = 'ご利用日時：' . $lastUsedCC;
                    $lastUsedCcInfo['description']['message'][] = '有効期限切れ等でクレジット情報を変更する場合は、[クレジット支払]を選択してください';
                    if($currentPaymentMethod == $paymentMethod['value'] && $profileCache->getData('is_new_paygent_method') == false){
                        $response['default_payment_method'] = $paymentMethod['value'];
                    }
                    $paymentMethodList[] = $lastUsedCcInfo;
                }
                $paymentMethod['value'] = 'new_paygent';
                $paymentMethod['label'] = 'クレジットカード（カード情報入力へ進む）';
            }
            $paymentMethod['description'] = $this->getPaymentMethodDescription($paymentMethod);
            $paymentMethodList[] = $paymentMethod;
        }
        $response['payment_method_list'] = $paymentMethodList;
        $response['can_change_payment_method'] = $isAllowChangePaymentMethod == 1 ? true : false;
        return $response;
    }

    protected function getPaymentMethodDescription($paymentMethod){
        $description = ['message' => []];
        switch($paymentMethod['value']){
            case "cashondelivery":
                $description['message'][] = __('About COD handling fees');
                $description['message'][] = __('A per-shipment collection handling fee of %1 yen (tax included) applies to all COD orders.', isset($paymentMethod['params']['price']) ? floatval($paymentMethod['params']['price']) : 0);
                $description['message'][] = __('There are some items that COD handling fee is free');
                break;
            case "cvspayment":
                $description['message'][] = __('We send you a transfer form before shipping out your order. Please pay at convenience store within the period.');
                $description['message'][] = __('The transfer form will be delivered after 4 days from order complete.');
                $description['message'][] = __('The due date will be 10 days after issuing date.');
                $description['message'][] = __('Also, please make sure to finish your payment in 30 days, otherwise your order will be cancelled.');
                $description['message'][] = $this->blockFactory->createBlock("\Magento\Framework\View\Element\Template")->getViewFileUrl('images/cvs-payment-form.jpg');
                break;
            case "npatobarai":
                $description['message'][] = $this->blockFactory->createBlock("\Magento\Framework\View\Element\Template")->getViewFileUrl('images/np_atobarai_payment.png');
                $description['message'][] = '○このお支払方法の詳細';
                $description['message'][] = '商品の到着を確認してから、「コンビニ」「郵便局」「銀行」「LINE Pay」で';
                $description['message'][] = '後払いできる安心・簡単な決済方法です。請求書は、商品とは別に';
                $description['message'][] = '郵送されますので、発行から14日以内にお支払いをお願いします。';
                $description['message'][] = __('後払い手数料： %1円', 0);
                $description['message'][] = '後払いのご注文には、<a href="https://www.netprotections.com/" target="_blank">株式会社ネットプロテクションズ</a>の提供する';
                $description['message'][] = 'NP後払いサービスが適用され、サービスの範囲内で個人情報を提供し、';
                $description['message'][] = '代金債権を譲渡します。';
                $description['message'][] = 'ご利用限度額は累計残高で55,000円（税込）迄です。';
                $description['message'][] = '詳細はバナーをクリックしてご確認下さい。';
                $description['message'][] = 'ご利用者が未成年の場合、法定代理人の利用同意を得てご利用ください。';
                break;
            case "new_paygent":
                $description['message'][] = __('Please enter card information from the Go to Enter Card Information on the next page');
                $description['message'][] = $this->blockFactory->createBlock("\Magento\Framework\View\Element\Template")->getViewFileUrl('images/credit_card_method.png');
                $description['message'][] = __('Points to remember about credit card with debit function');
                $description['message'][] = __('Credit card with debit function will be withdrawn when card information is entered.~');
                break;
            default:
                break;
        }
        return $description;
    }

    protected function getFrequencyList($profileId){
        $list = [];
        $course = $this->courseFactory->create()->load($this->profileRepository->get($profileId)->getCourseId());
        if ($course->getId()) {
            $frequencies = $course->getFrequencyEntities();
            foreach ($frequencies as $frequency) {
                if (isset($frequency['frequency_id'])) {
                    $list[] = [
                        'frequency_id' => (int)$frequency['frequency_id'],
                        'label' => __($frequency['frequency_interval']) . __($frequency['frequency_unit'])
                    ];
                }
            }
        }
        return $list;
    }

    protected function customerOwnsProfile($profileId, $customerId){
        $profile = $this->profileRepository->get($profileId);
        if($profile->getCustomerId() == $customerId){
            return true;
        } else {
            return false;
        }
    }

    protected function checkProfileAvailable($profileId, $customerId){
        $profileList = $this->loadProfileList($customerId);
        $response = ['available' => true];
        if(!$this->customerOwnsProfile($profileId, $customerId)){
            $response['available'] = false;
            $response['content'] = $this->generateResponse(null, self::CUSTOMER_NOT_OWN_PROFILE);
        } else if(!in_array($profileId, $profileList)){
            $response['available'] = false;
            $response['content'] = $this->generateResponse(null, self::PROFILE_NOT_AVAILABLE);
        }
        return $response;
    }

    protected function loadProfileList($customerId){
        $profileList = [];
        /** @var \Riki\Subscription\Model\Profile\ResourceModel\Profile\Collection $profileCollection */
        $profileCollection = $this->profileCollectionFactory->create();
        $profileCollection->addFieldToFilter('customer_id', $customerId);
        $profileCollection->addFieldToFilter('type', [
            ['neq' => Profile::SUBSCRIPTION_TYPE_TMP],
            ['null' => true]
        ])->addFieldToFilter('status', 1);
        $profileCollection->getSelect()->joinLeft(
            ['subscription_course' => 'subscription_course'],
            "main_table.course_id = subscription_course.course_id",
            [
                'subscription_course.course_name',
                'subscription_course.allow_skip_next_delivery',
                'subscription_course.allow_change_product',
                'subscription_course.is_allow_cancel_from_frontend',
                'subscription_course.minimum_order_times',
                'subscription_course.subscription_type',
            ]
        )->where(
            'subscription_course.subscription_type != ?',
            \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI
        );
        //should not display disengaged profiles
        $profileCollection->addFieldToFilter('disengagement_user', ['null' => true]);
        $profileCollection->addFieldToFilter('disengagement_date', ['null' => true]);
        $profileCollection->addFieldToFilter('disengagement_reason', ['null' => true]);
        $profileCollection->addOrder('profile_id', 'DESC');
        $profileCollection->getSelect()->limit(400);

        if($profileCollection->getSize() > 0){
            foreach($profileCollection as $profile){
                $profileList[] = $profile->getId();
            }
        }

        return $profileList;
    }

    protected function generateResponse($content = null, $code = null, $e = null, $additionalInfo = null){
        $response = [
            'data' => [
                'code' => 200,
                'message' => [
                    "type" => "success",
                    "text" => "Success"
                ],
                'redirect' => null,
                'content' => $content
            ]
        ];
        if($code === self::CUSTOMER_NOT_OWN_PROFILE){
            $response['data']['message']['type'] = "warning";
            $response['data']['message']['text'] = "User does not own this profile";
        } else if($code === self::PROFILE_NOT_AVAILABLE){
            $response['data']['message']['type'] = "warning";
            $response['data']['message']['text'] = "User can't view this profile";
        } else if($code === self::UNAVAILABLE_INPUT_DATA){
            $response['data']['message']['type'] = "warning";
            if($additionalInfo != null){
                $response['data']['message']['text'] = $additionalInfo;
            } else {
                $response['data']['message']['text'] = "Incorrect input data";
            }
        } else if($code === self::SOME_ERROR_HAPPENS){
            $response['data']['message']['type'] = "exception";
            $response['data']['message']['text'] = "Some error happens";
            if($e != null) {
                $this->logger->critical('[NET-18-LandingAPI]: ' . $e->getMessage());
            }
        } else if($code === self::REDIRECT){
            $response['data']['redirect'] = $additionalInfo;
        } else if($code == null && $additionalInfo != null){
            $response['data']['message']['text'] = $additionalInfo;
        }
        $this->stopEmulation();
        return $response;
    }

    protected function loadCurrentProfileInfo($profileId){
        $profile = $this->profileRepository->get($profileId);
        $currentProfile = ['id' => $profileId];
//        $currentProfile['prepare_shipment_list'] = $this->getPrepareShipmentList($profile);
//        $currentProfile['modifiable_order'] = $this->getModifiableOrder($profile);
        return $currentProfile;
    }

    protected function loadCurrentProfileInfoNoSimulate($profileList, $customerId = null){
        $response = [];
        $prepareShipmentList = $this->getPrepareShipmentList($profileList);
        $modifiableOrderList = $this->deliveryDateHelper->getModifiableOrderNoSimulate($profileList);
        foreach($profileList as $profileId){
            $response[] = [
                'id' => $profileId,
                'prepare_shipment_list' => key_exists($profileId, $prepareShipmentList) ? $prepareShipmentList[$profileId] : null,
                'modifiable_order' => key_exists($profileId, $modifiableOrderList) ? $modifiableOrderList[$profileId] : null,
            ];
        }
        $customerCode = $this->customerModelFactory->create()->load($customerId)->getData('consumer_db_id');
        $pointResult = $this->shoppingPoint->getPoint($customerCode, ShoppingPoint::TYPE_POINT);
        $balance = 0;
        if (!$pointResult['error']) {
            $balance = $pointResult['return']['REST_POINT'];
        }
        $usePointAmount = 0;
        $consumerData = $this->consumerCustomerRepository->prepareInfoSubCustomer($customerCode);
        if (isset($consumerData['USE_POINT_TYPE'])) {
            if($consumerData['USE_POINT_TYPE'] == RewardQuote::USER_DO_NOT_USE_POINT) {
                $usePointAmount = 0;
            } else if($consumerData['USE_POINT_TYPE'] == RewardQuote::USER_USE_ALL_POINT){
                $usePointAmount = $balance;
            } else if($consumerData['USE_POINT_TYPE'] == RewardQuote::USER_USE_SPECIFIED_POINT){
                if (isset($consumerData['USE_POINT_AMOUNT'])) {
                    $usePointAmount = $consumerData['USE_POINT_AMOUNT'];
                } else {
                    $usePointAmount = 0;
                }
            }
        }
        foreach($response as &$r){
            $r['modifiable_order']['use_point_amount'] = $usePointAmount;
        }
        return $response;
    }

    protected function getPrepareShipmentList($profileList)
    {
        $orderResponse = [];
        $itemCollection = $this->orderItemCollectionFactory->create();
        $orderTable = $itemCollection->getConnection()->getTableName('sales_order');
        $orderStatusTable = $itemCollection->getConnection()->getTableName('sales_order_status');
        $orderStatusStateTable = $itemCollection->getConnection()->getTableName('sales_order_status_state');
        $itemCollection->getSelect()->joinLeft(['order' => $orderTable], 'main_table.order_id = order.entity_id', ['status', 'grand_total', 'subscription_profile_id'])
        ->joinLeft(['order_status_label' => $orderStatusTable], 'order.status = order_status_label.status')
        ->joinLeft(['order_status_state' => $orderStatusStateTable], 'order.status = order_status_state.status', ['visible_on_front']);
        $itemCollection->addFieldToFilter('subscription_profile_id', ['in' => $profileList])
            ->addFieldToFilter('order_status_state.visible_on_front', ['eq' => 1])
            ->setOrder('order.entity_id', "DESC");
        foreach($itemCollection as $index => $item){
            $orderId = $item->getData('order_id');
            $orderResponse[$item->getData('subscription_profile_id')][$orderId]['order_link'] = $this->urlBuilder->getUrl('sales/order/view/', ['order_id' => $orderId, '_secure' => true]);
            $orderResponse[$item->getData('subscription_profile_id')][$orderId]['order_status'] = __($item->getData('label'));
            $orderResponse[$item->getData('subscription_profile_id')][$orderId]['grandtotal'] = $item->getData('grand_total');
            $itemDate = $item->getData('delivery_date');
            $orderResponse[$item->getData('subscription_profile_id')][$orderId]['delivery_date']
                = $orderResponse[$item->getData('subscription_profile_id')][$orderId]['delivery_date'] ?? date('Y-m-d', strtotime('+5 years'));
            if($index == 0){
                $orderResponse[$item->getData('subscription_profile_id')][$orderId]['delivery_date'] = $itemDate;
            }
            if($itemDate <= $orderResponse[$item->getData('subscription_profile_id')][$orderId]['delivery_date']){
                $orderResponse[$item->getData('subscription_profile_id')][$orderId]['delivery_date'] = $itemDate;
            }
        }
        // reformat output
        $finalResponse = [];
        foreach($orderResponse as $index => $profile){
            foreach($profile as $o){
                $finalResponse[$index][] = $o;
                break; // only get latest order
            }
        }
        return $finalResponse;
    }

    protected function getModifiableOrder($profile)
    {
        return $this->deliveryDateHelper->getModifiableOrder($profile->getProfileId());
    }

    protected function startEmulation(){
        $this->appEmulation->startEnvironmentEmulation($this->storeManager->getStore()->getId(), \Magento\Framework\App\Area::AREA_FRONTEND, true);
        $this->translator->loadData(null, true);
    }

    protected function stopEmulation(){
        $this->appEmulation->stopEnvironmentEmulation();
    }
}