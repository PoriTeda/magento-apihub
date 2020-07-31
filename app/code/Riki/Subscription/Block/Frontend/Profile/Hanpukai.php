<?php

namespace Riki\Subscription\Block\Frontend\Profile;

use \Riki\Subscription\Helper\Order\Simulator as HelperOrderSimulator;

class Hanpukai extends \Magento\Framework\View\Element\Template
{
    /*@var \Magento\Framework\Registry */
    protected $coreRegistry;

    /*@var \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory */
    protected $profileModelCollectionFactory;

    /*@var \Riki\Subscription\Helper\Profile\Data */
    protected $_helperProfile;

    /* @var HelperOrderSimulator */
    protected $helperSimulator;

    protected $_timezone;

    /* @var \Riki\Subscription\Helper\Hanpukai\Data */
    protected $hanpukaiHelper;

    /* @var \Magento\Framework\View\Page\Config */
    protected $_pageConfig;

    protected $_profileIndexer;

    /**
     * @var \Riki\Subscription\Model\Profile\Profile
     */
    protected $profileModel;

    protected $modelProfile;
    /**
     * @var \Riki\Subscription\Helper\Indexer\Data
     */
    protected $profileIndexerHelper;

    /**
     * @var \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper
     */
    protected $deliveryDateGenerateHelper;

    /**
     * Construct
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\Subscription\Helper\Hanpukai\Data $hanpukaiHelper,
        HelperOrderSimulator $helperOrderSimulator,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory $profileCollectionFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $profileIndexer,
        \Riki\Subscription\Model\Profile\Profile     $profileModel,
        \Riki\Subscription\Helper\Indexer\Data $profileIndexerHelper,
        \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper,
        array $data = []
    ){
        $this->_profileIndexer = $profileIndexer;
        $this->_pageConfig = $context->getPageConfig();
        $this->hanpukaiHelper = $hanpukaiHelper;
        $this->_timezone = $context->getLocaleDate();
        $this->helperSimulator = $helperOrderSimulator;;
        $this->_helperProfile = $helperProfile;
        $this->coreRegistry = $registry;
        $this->profileModelCollectionFactory = $profileCollectionFactory;
        $this->profileModel = $profileModel;
        $this->profileIndexerHelper = $profileIndexerHelper;
        $this->deliveryDateGenerateHelper = $deliveryDateGenerateHelper;
        parent::__construct($context, $data);
    }


    /**
     * Get customer id
     *
     * @return int
     */
    public function getCustomerId()
    {
        $customerId = $this->coreRegistry->registry('current_subscription_profile_customer');
        return $customerId;
    }

    /* @return bool|\Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection */
    public function getListProfile()
    {
        $customerId = $this->getCustomerId();
        if (!$customerId) {
            return false;
        }

        $collection = $this->profileModelCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->addFieldToFilter('status', 1)
            ->addFieldToFilter('type', array(
                array('neq' => \Riki\Subscription\Model\Profile\Profile::SUBSCRIPTION_TYPE_TMP),
                array('null' => true)));
        $collection->getSelect()->join(
            array('sub_course' => 'subscription_course'),
            'sub_course.course_id = main_table.course_id',
            ['sub_course.hanpukai_type', 'sub_course.course_name']
        )->where('sub_course.hanpukai_type IN ("hfixed", "hsequence")');
        $collection->addOrder('profile_id', 'DESC');
        return $collection;
    }

    public function threeDeliveryDate()
    {
        $arrResult = array();
        $profileCollection  = $this->getListProfile();
        foreach($profileCollection as $profileItem) {
            $profileId = $profileItem->getData('profile_id');
            $arrThreeDelivery = $this->_helperProfile->calculateNextDelivery($profileItem,true);
            $arrResult[$profileId]['course_name'] = $profileItem->getData('course_name');
            $arrResult[$profileId]['next_delivery_amount']['total_amount'] = $this->getOrderTotalAmount($profileId);
            $arrResult[$profileId]['next_delivery_1'] = $arrThreeDelivery[0];
            $arrResult[$profileId]['next_delivery_2'] = $arrThreeDelivery[1];
            $arrResult[$profileId]['next_delivery_3'] = $arrThreeDelivery[2];
        }
        return $arrResult;
    }

    /**
     * Check main profile have tmp
     *
     * @param $profileId
     * @return bool
     */
    public function isProfileHaveTmp($profileId)
    {
        if ($this->_helperProfile->getTmpProfile($profileId) == false) {
            return false;
        } else {
            return $this->_helperProfile->getTmpProfile($profileId)->getData('linked_profile_id');
        }
    }

    /**
     * Get link edit sub profile
     *
     * @param
     * @return string
     */
    public function getLinkSubProfile($profileId)
    {
        if ($profileId) {
            return $this->getBaseUrlSubcriptionProfile($profileId);
        } else {
            return '';
        }

    }

    public function getBaseUrlSubcriptionProfile($id)
    {
        return $this->getUrl('subscriptions/profile/hanpukaiPlan/id/' . (int)$id);
    }

    /**
     * @param $profileId
     * @return array|bool
     * @throws \Exception
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getSimulatorOrderOfProfile($profileId)
    {
        $isList = true; // if simulate from list not get point
        try {
            $simulatorOrder = $this->helperSimulator->createMageOrder($profileId, null, true, null, $isList);
            if ($simulatorOrder instanceof \Riki\Subscription\Model\Emulator\Order) {
                return $simulatorOrder;
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }

        return false;
    }

    /**
     * Get order total amount
     *
     * @param $profileId
     * @return \Magento\Framework\Phrase
     */
    public function getOrderTotalAmount($profileId)
    {
        $data = $this->_profileIndexer->loadSimulateDataByProfileId($profileId);
        if ($data && $data['data_serialized']) {
            $data = \Zend\Serializer\Serializer::unserialize($data['data_serialized']);
            return $data['total_amount'];
        }

        $orderSimulator = $this->getSimulatorOrderOfProfile($profileId);
        if ($orderSimulator === false)
        {
            return __('Not Yet Calculator');
        } else {
            $this->addCacheProfileIndexer($profileId,$orderSimulator);
            return $orderSimulator->getGrandTotal();
        }
    }

    /**
     * Convert all date to true format YYYY/mm/dd
     *
     * @param string $date
     *
     * @return string
     */
    public function convertDateToTrueFormat($date)
    {
        return $this->_timezone->date($date)->format('Y/m/d');
    }

    /**
     * Prepare global layout
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->_pageConfig->getTitle()->set(__('Hanpukai List'));

        return parent::_prepareLayout();
    }

    /**
     * Format currency
     *
     * @param $price
     * @param null $websiteId
     *
     * @return mixed
     */

    public function formatCurrency($price)
    {
        if (is_numeric($price)) {
            return $this->_storeManager->getWebsite($this->_storeManager->getStore()->getWebsiteId())
                ->getBaseCurrency()->format($price);
        } else {
            return $price;
        }
    }
    /**
     * is show changing payment method link
     *
     * @param $profileId
     * @return bool
     */
    public function showChangePaymentMethodLink($profileId) {
        if ($this->modelProfile and isset($this->modelProfile[$profileId])) {
            return $this->modelProfile[$profileId];
        }
        $profileModel = $this->profileModel->load($profileId);
        $this->modelProfile[$profileId] = $profileModel;
        if ($this->_helperProfile->checkProfileHaveTmp($profileId) and is_null($profileModel->getPaymentMethod())) {
            return true;
        }
        return false;
    }
    /**
     * Add cache for profile list when does not have cache
     *
     * @param $profileId
     * @param $simulatorOrder
     */
    public function addCacheProfileIndexer($profileId,$simulatorOrder) {
        $this->coreRegistry->unregister('reindex_cache_profile');
        $this->coreRegistry->register('reindex_cache_profile',true);
        $dataSimulate = $this->profileIndexerHelper->prepareData($simulatorOrder);

        if ($dataSimulate) {
            $serializedData = \Zend\Serializer\Serializer::serialize($dataSimulate);
            $dataTable = [
                'profile_id' => $profileId,
                'customer_id' => $simulatorOrder->getCustomerId(),
                'data_serialized' => $serializedData
            ];
            $profileIds[] = $profileId;
            $this->profileIndexerHelper->saveToTable($dataTable);
        }
        /*** end get data from simulator ***/
        $this->profileIndexerHelper->makeCacheDataForHanpukai($profileId);
        /*update reindex flag*/
        $this->profileIndexerHelper->updateProfile($profileId);
    }
}