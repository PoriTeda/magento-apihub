<?php

namespace Riki\Subscription\Model\Simulator;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class OrderSimulator implements \Riki\Subscription\Api\Simulator\OrderSimulatorInterface
{
    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $_profileRepository;

    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_sessionManagerInterface;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_helperProfileData;

    /**
     * @var \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    public $_extensibleDataObjectConverter;

    /**
     * @var \Riki\CatalogRule\Helper\Data
     */
    protected $_catalogRuleHelper;
    /**
     * @var \Magento\Framework\Registry
     */
    public $_registry;

    /**
     * @var \Riki\Subscription\Helper\Order\Simulator
     */
    protected $_helperOrderSimulator;

    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $_helperWrapping;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Tax\Model\TaxCalculation
     */
    public $_taxCalculation;

    protected $_wrappingCollectionFactory;

    protected $dataOrderSimulator;

    public $_subscriptionProfile;

    public $_subscriptionProfileObj;

    public $_subscriptionProfileData;

    /**
     * @var \Magento\GiftMessage\Model\MessageFactory
     */
    public $_messageFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */

    public $_timezoneHelper;
    /**
     * @var \Riki\Subscription\Helper\CalculateDeliveryDate
     */
    public $_calculateDeliveryDate;

    /**
     * @var \Magento\Catalog\Helper\Image
     */
    public $_helperImage;

    /**
     * OrderSimulator constructor.
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Riki\Subscription\Helper\Order\Simulator $helperOrderSimulator
     * @param \Magento\Framework\Session\SessionManagerInterface $sessionManagerInterface
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfileData
     * @param \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter
     * @param \Riki\CatalogRule\Helper\Data $catalogRuleHelper
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\Subscription\Helper\Order\Simulator $helperOrderSimulator,
        \Magento\Framework\Session\SessionManagerInterface $sessionManagerInterface,
        \Riki\Subscription\Helper\Profile\Data $helperProfileData,
        \Magento\Framework\Api\ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        \Riki\CatalogRule\Helper\Data $catalogRuleHelper,
        \Magento\Framework\Registry $registry,
        \Psr\Log\LoggerInterface $logger,
        \Magento\GiftWrapping\Helper\Data $helperWrapping,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Tax\Model\TaxCalculation $taxCalculation,
        \Magento\GiftWrapping\Model\ResourceModel\Wrapping\CollectionFactory $wrappingCollectionFactory,
        \Magento\GiftMessage\Model\MessageFactory $messageFactory,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezoneHelper,
        \Riki\Subscription\Helper\CalculateDeliveryDate $calculateDeliveryDate,
        \Magento\Catalog\Helper\Image $helperImage
    )
    {
        $this->_sessionManagerInterface = $sessionManagerInterface;
        $this->_helperProfileData = $helperProfileData;
        $this->_extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->_catalogRuleHelper = $catalogRuleHelper;
        $this->_registry = $registry;
        $this->_profileRepository = $profileRepository;
        $this->_helperOrderSimulator = $helperOrderSimulator;
        $this->_logger = $logger;
        $this->_helperWrapping = $helperWrapping;
        $this->_storeManager = $storeManager;
        $this->_taxCalculation = $taxCalculation;
        $this->_wrappingCollectionFactory = $wrappingCollectionFactory;
        $this->_messageFactory = $messageFactory;
        $this->_timezoneHelper = $timezoneHelper;
        $this->_calculateDeliveryDate = $calculateDeliveryDate;
        $this->_helperImage = $helperImage;
    }

    /**
     * {@inheritdoc}
     */
    public function processOrderSimulator($profileId)
    {

        /**
         * @var \Riki\Subscription\Model\Profile\Profile $objProfile
         */
        $objProfile = $this->_helperProfileData->load($profileId);

        /**
         * @var \Magento\Framework\Session\SessionManagerInterface $objSession
         */
        if ($this->_sessionManagerInterface->getProfileData() == null) {
            $productCartData = $objProfile->getProductCartData();

            /**
             * @var \Magento\Framework\DataObject $obj
             */
            $obj = new DataObject();
            $obj->setData($objProfile->getData());
            $obj->setData("course_data", $objProfile->getCourseData());
            $obj->setData("product_cart", $productCartData);
            $this->_sessionManagerInterface->setProfileData([$profileId => $obj]);
        }

        $productIds = [];
        $productCartItems = $objProfile->getProductCartData();
        foreach ($productCartItems as $productCart) {
            $productIds[] = $productCart->getData('product_id');
        }

        /**
         * improve performance by decrease load catalog rule
         */
        if ($productIds) {
            /** @var \Riki\CatalogRule\Helper\Data $catalogRuleHelper */
            $this->_catalogRuleHelper->registerPreLoadedProductIds($productIds);
        }

        /**
         * @var \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
         */
        $profileDataModel = $this->_profileRepository->get($profileId);

        //set global data for profile
        $this->_subscriptionProfile = $this->_sessionManagerInterface->getProfileData()[$profileId];
        $this->_registry->register('subscription_profile', $this->_subscriptionProfile);

        $this->_subscriptionProfileData = $objProfile;
        $this->_registry->register('subscription_profile_obj', $this->_subscriptionProfileData);

        $this->_subscriptionProfileData = $profileDataModel;
        $this->_registry->register('subscription_profile_data', $this->_subscriptionProfileData);

        $courseId = $objProfile->hasData('course_id') ? $objProfile->getData('course_id') : 0;
        $frequencyId = $objProfile->getSubProfileFrequencyID();

        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);
        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);
        $this->dataOrderSimulator = $this->getSimulatorOrderOfProfile($profileDataModel->getProfileId());
        return $this;
    }

    /**
     * @param $profileId
     * @return bool|object
     */
    public function getSimulatorOrderOfProfile($profileId)
    {
        $sessionProfile = $this->_sessionManagerInterface->getProfileData();
        if ($sessionProfile and isset($sessionProfile[$profileId])) {
            try {
                $simulatorOrder = $this->_helperOrderSimulator->createSimulatorOrderHasData($sessionProfile[$profileId], null, true);
                if ($simulatorOrder instanceof \Riki\Subscription\Model\Emulator\Order) {
                    return $simulatorOrder;
                }
            } catch (LocalizedException $e) {
                $this->_logger->info($e->getMessage());
            } catch (\Exception $e) {
                $this->_logger->critical($e);
            }
        }
        return false;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->_subscriptionProfile;
    }


    /**
     * @return \Magento\Framework\Api\ExtensibleDataObjectConverter
     */
    public function getExtensibleDataObjectConverter()
    {
        return $this->_extensibleDataObjectConverter;
    }

    /**
     * @return mixed
     */
    public function getDataOrderSimulator()
    {
        return $this->dataOrderSimulator;
    }

}