<?php

namespace Riki\SubscriptionCutOffEmail\Helper;

use Symfony\Component\Config\Definition\Exception\Exception;
use Zend\I18n\Validator\DateTime;
use Magento\Framework\Exception\MailException;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const CONFIG_CUT_OFF_EMAIL_ENABLE = 'subscriptioncourse/cutoffdate/enable';
    const CONFIG_CUT_OFF_EMAIL_TEMPLATE = 'subscriptioncourse/cutoffdate/email_template';
    const CONFIG_CUT_OFF_EMAIL_SENDER = 'subscriptioncourse/cutoffdate/sender';
    const CONFIG_CUT_OFF_EMAIL_SEND_EMAIL_COPY_METHOD = 'subscriptioncourse/cutoffdate/send_email_copy_method';
    const CONFIG_CUT_OFF_EMAIL_SEND_EMAIL_COPY_TO = 'subscriptioncourse/cutoffdate/send_email_copy_to';
    const CONFIG_CUT_OFF_EMAIL_X_DAYS_BEFORE_CUT_OFF_DATE = 'subscriptioncourse/cutoffdate/x_days_before_cut_off_date';
    const CONFIG_CUT_OFF_EMAIL_Y_DAYS_BEFORE_CUT_OFF_DATE = 'subscriptioncourse/cutoffdate/y_days_before_cut_off_date';
    const CONFIG_BUFFER_DATE = 'shipleadtime/shipping_buffer_days/shipping_couriers_common_buffer';

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    protected $inlineTranslate;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    protected $transportBuilder;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory
     */
    protected $subscriptionProfileCollectionFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepositoryInterface;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $stdTimezone;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $profileRepository;
    /**
     * @var \Magento\Customer\Model\AddressFactory
     */
    protected $customerAddress;
    /**
     * @var \Magento\Directory\Model\RegionFactory
     */
    protected $regionFactory;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Riki\ShipLeadTime\Model\LeadtimeFactory
     */
    protected $leadTimeFactory;
    /**
     * @var \Riki\DeliveryType\Helper\Data
     */
    protected $helperDelivery;

    /**
     * @var \Riki\SubscriptionCutOffEmail\Logger\SendCutOffEmailLogger
     */
    protected $sendCutOffEmailLogger;

    /**
     * Data constructor.
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Translate\Inline\StateInterface $translation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory $collectionProfileFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone
     * @param \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository
     * @param \Magento\Customer\Model\AddressFactory $customerAddress
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\ShipLeadTime\Model\LeadtimeFactory $leadtimeFactory
     * @param \Riki\DeliveryType\Helper\Data $helperDelivery
     * @param \Riki\SubscriptionCutOffEmail\Logger\SendCutOffEmailLogger $sendCutOffEmailLogger
     */
    public function __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $translation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory $collectionProfileFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\Framework\Stdlib\DateTime\Timezone $stdTimezone,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepository,
        \Magento\Customer\Model\AddressFactory $customerAddress,
        \Magento\Directory\Model\RegionFactory $regionFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\ShipLeadTime\Model\LeadtimeFactory $leadtimeFactory,
        \Riki\DeliveryType\Helper\Data $helperDelivery,
        \Riki\SubscriptionCutOffEmail\Logger\SendCutOffEmailLogger $sendCutOffEmailLogger
    ) {
    
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->stdTimezone = $stdTimezone;
        $this->dateTime = $dateTime;
        $this->timezone = $timezoneInterface;
        $this->subscriptionProfileCollectionFactory = $collectionProfileFactory;
        $this->storeManager = $storeManagerInterface;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslate = $translation;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customerAddress = $customerAddress;
        $this->profileRepository = $profileRepository;
        $this->regionFactory = $regionFactory;
        $this->productRepository = $productRepository;
        $this->leadTimeFactory = $leadtimeFactory;
        $this->helperDelivery = $helperDelivery;
        $this->sendCutOffEmailLogger = $sendCutOffEmailLogger;
        parent::__construct($context);
    }
    public function getConfig($path)
    {

        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $config = $this->scopeConfig->getValue($path, $storeScope);
        return $config;
    }
    public function getCutOffEmailTemplate()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        $template = $this->scopeConfig->getValue(self::CONFIG_CUT_OFF_EMAIL_TEMPLATE, $storeScope);
        return $template;
    }

    /**
     * @param $emailTemplateVariables
     * @param $emailReceiver
     * @return $this
     */
    public function generateTemplate($emailTemplateVariables)
    {
        $emailSender = $emailTemplateVariables['emailReceiver'];
        $this->transportBuilder->setTemplateIdentifier($this->getCutOffEmailTemplate())
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->storeManager->getStore()->getId(),
                ]
            )
            ->setTemplateVars($emailTemplateVariables)
            ->setFrom($this->getConfig(self::CONFIG_CUT_OFF_EMAIL_SENDER))
            ->addTo($emailSender);

        $copyTo = $this->getListEmail();
        if (!empty($copyTo) && $this->getConfig(self::CONFIG_CUT_OFF_EMAIL_SEND_EMAIL_COPY_METHOD) == 'bcc') {
            foreach ($copyTo as $email) {
                $this->transportBuilder->addBcc($email);
            }
        }
        return $this;
    }
    public function getListEmail()
    {
        $listEmail = $this->getConfig(self::CONFIG_CUT_OFF_EMAIL_SEND_EMAIL_COPY_TO);
        if ($listEmail == null) {
            return [];
        }
        return explode(',', trim($listEmail));
    }

    /**
     * Send Cut Off Email on cut-off-date - X days
     *
     * @param array $emailTemplateVariables
     * @return boolean
     */
    public function sendCutOffEmail($emailTemplateVariables)
    {
        try {
            $this->inlineTranslate->suspend();
            $this->generateTemplate($emailTemplateVariables);
            $transport = $this->transportBuilder->getTransport();
            $transport->setRelationEntityId($emailTemplateVariables['subscription_profile_id']);
            $transport->setRelationEntityType('Cut-off Email');
            $transport->sendMessage();
            $this->inlineTranslate->resume();
            return true;
        } catch (MailException $e) {
            $this->sendCutOffEmailLogger->info(sprintf(
                "Profile ID [%s] cannot send cut off email due to : [%s].",
                $emailTemplateVariables['subscription_profile_id'],
                $e->getMessage()
            ));
        } catch (\Exception $e) {
            $this->sendCutOffEmailLogger->critical($e);
        }

        return false;
    }

    public function getSubscriptionProfileCollection()
    {
        $xDaysConfig = (int)$this->getConfig(self::CONFIG_CUT_OFF_EMAIL_X_DAYS_BEFORE_CUT_OFF_DATE);

        $dateTimeNow = $this->stdTimezone->date();
        $dateInterval = \DateInterval::createFromDateString($xDaysConfig . ' ' . 'day');
        $dateTimeNow->add($dateInterval);

        $profileCollection = $this->subscriptionProfileCollectionFactory->create();

        $profileCollection->addFieldToFilter('next_order_date', $dateTimeNow->format('Y-m-d'));
        $profileCollection->addFieldToFilter('status', 1);

        return $profileCollection;
    }

    public function getCustomerById($customerId)
    {
        return $this->customerRepositoryInterface->getById($customerId);
    }

    /**
     * @param $startDate
     * @param $endDate
     * @return float
     */
    public function getDiffDay($startDate, $endDate)
    {
        $startDateObj = date_create($this->dateTime->gmtDate('Y-m-d', $startDate));
        $endDateObj = date_create($this->dateTime->gmtDate('Y-m-d', $endDate));
        return date_diff($startDateObj, $endDateObj)->days +
            $xDaysConfig = (int)$this->getConfig(self::CONFIG_CUT_OFF_EMAIL_X_DAYS_BEFORE_CUT_OFF_DATE);
    }

    /**
     * @param $cutoffdate
     * @return array
     */
    public function getDatesBeforeCutoffDate($cutoffdate)
    {
        $configXDays = (int)$this->getConfig(self::CONFIG_CUT_OFF_EMAIL_X_DAYS_BEFORE_CUT_OFF_DATE);
        $configYDays = (int)$this->getConfig(self::CONFIG_CUT_OFF_EMAIL_Y_DAYS_BEFORE_CUT_OFF_DATE);
        $xdate = $this->dateTime->gmtDate('Y/m/d', strtotime($cutoffdate . '-' . $configXDays . ' day'));
        $ydate = $this->dateTime->gmtDate('Y/m/d', strtotime($cutoffdate . '-' . $configYDays . ' day'));
        return ['xdate' => $xdate, 'ydate' => $ydate, 'xdateconfig' => $configXDays];
    }

    public function calculateXYDate($profileId)
    {
        // <X> = X days (configurable) + Lead time + WH holidays + Buffer day
        // <Y> = Y days (configurable) + Lead time + WH holidays + Buffer day
        $configXDays = (int)$this->getConfig(self::CONFIG_CUT_OFF_EMAIL_X_DAYS_BEFORE_CUT_OFF_DATE);
        $configYDays = (int)$this->getConfig(self::CONFIG_CUT_OFF_EMAIL_Y_DAYS_BEFORE_CUT_OFF_DATE);
        $bufferDays = (int)$this->getConfig(self::CONFIG_BUFFER_DATE);
        $productCartModel = $this->profileRepository->getListProductCart($profileId);
        $arrShippingAddress = [];
        $regionIds = [];
        $productIds = [];
        foreach ($productCartModel->getItems() as $productCartItem) {
            $productIds[] = $productCartItem->getProductId();
            $arrShippingAddress[] = $productCartItem->getShippingAddressId();
        }
        if ($arrShippingAddress) {
            $addressModel = $this->customerAddress->create()->getCollection();
            $addressModel->addFieldToFilter('entity_id', $arrShippingAddress);
            foreach ($addressModel as $address) {
                $regionIds[] = $address->getRegionId();
            }
        }
        $homeAddress = null;
        if (empty($regionIds)) {
            $profileModel = $this->profileRepository->get($profileId);
            $customerModel = $this->getCustomerById($profileModel->getCustomerId());
            foreach ($customerModel->getAddresses() as $address) {
                $addressType =  $address->getCustomAttribute('riki_type_address');
                if ($addressType instanceof \Magento\Framework\Api\AttributeValue) {
                    if ($addressType->getValue() ==\Riki\Customer\Model\Address\AddressType::HOME) {
                        $homeAddress = $address;
                        break;
                    }
                }
            }
        }
        if ($homeAddress instanceof \Magento\Customer\Model\Data\Address) {
            $regionIds[] = $homeAddress->getRegionId();
            $newAddressForProfile = $homeAddress->getId();
            foreach ($productCartModel->getItems() as $productCartItem) {
                $productCartItem->setData('shipping_address_id', $newAddressForProfile);
                try {
                    $productCartItem->save();
                } catch (\Exception $e) {
                    $this->sendCutOffEmailLogger->critical($e);
                }
            }
        }
        //get leadtime
        $prefecture = $this->getPrefectureCodeOfRegion($regionIds);
        $originDate = $this->timezone->formatDateTime($this->dateTime->gmtDate(), 2);
        $today = $this->dateTime->gmtDate('Y-m-d', $originDate);
        $dayDeducted = $bufferDays;
        $holidays = 0;
        //calculate date
        if ($prefecture) {
            $deliveryType = $this->getDeliveryTypeByProductIds($productIds);
            $leadtime = $this->getShippingLeadTimeByPrefecture($prefecture, $deliveryType);
            if ($leadtime and $posId = $leadtime->getData('warehouse_id')) {
                $dayDeducted = (int)$leadtime->getData('shipping_lead_time') + $bufferDays;
                for ($i = 1; $i <= $dayDeducted; $i++) {
                    $dateTmp = $this->dateTime->date('Y-m-d', strtotime($today . " +" . $i . " day"));
                    if ($this->checkWHHoliday($dateTmp, $posId)) {
                        $holidays++;
                    }
                }
            }
        }
        // $dayDeducted = lead time + buffer day + holidays
        $xdays = $configXDays + $dayDeducted + $holidays;
        $ydays = $configYDays + $dayDeducted + $holidays;
        return ['xdate' => $xdays, 'ydate' => $ydays, 'xdateconfig' => $configXDays, 'ydateconfig' => $configYDays];
    }

    /**
     * @param $arrRegion
     * @return array
     */
    public function getPrefectureCodeOfRegion($arrRegion)
    {
        if (!$arrRegion) {
            return [];
        }
        $regionModel = $this->regionFactory->create()->getCollection();
        $regionCode = $regionModel->addFieldToFilter('main_table.region_id', $arrRegion);
        $prefecture = [];
        foreach ($regionCode as $region) {
            $prefecture[] = $region->getCode();
        }
        return $prefecture;
    }

    /**
     * @param $productIds
     * @return array
     */
    public function getDeliveryTypeByProductIds($productIds)
    {
        $deliveryType = [];
        $info = $this->searchCriteriaBuilder->addFilter('entity_id', $productIds, 'in')->create();
        $productList = $this->productRepository->getList($info);
        if ($productList->getItems()) {
            foreach ($productList->getItems() as $item) {
                try {
                    $deliveryType[] = $item->getCustomAttribute('delivery_type')->getValue();
                } catch (\Exception $e) {
                    $this->sendCutOffEmailLogger->critical($e);
                }
            }
        }
        if (empty($deliveryType)) {
            $deliveryType[] = 'normal';
        }
        return $deliveryType;
    }

    /**
     * Get MAX shipping lead time by prefecture (apply only for subscription module)
     *
     * @param $prefecture : code prefecture
     * @param $deliveryType : array deliveryType
     * @return mixed
     */
    public function getShippingLeadTimeByPrefecture($prefecture, $deliveryType, $callback = true)
    {
        $leadTimeModel = $this->leadTimeFactory->create()->getCollection()->addActiveToFilter();
        $leadTimeModel->addFieldToFilter('pref_id', $prefecture);
        $leadTimeModel->addFieldToFilter('delivery_type_code', $deliveryType);
        $leadTimeModel->setOrder('shipping_lead_time', 'DESC');
        if (!empty($leadTimeModel)) {
            return $leadTimeModel->getFirstItem();
        } else {
            $tokyo = $this->regionFactory->create()->load('Tokyo', 'default_name');
            if ($tokyo->getId()) {
                $prefecture[] = $tokyo->getCode();
                if (!$callback) {
                    return false;
                }
                return $this->getShippingLeadTimeByPrefecture($prefecture, $deliveryType, false);
            }
        }
        return false;
    }

    /**
     * @param $date
     * @param $posId
     * @return bool
     */
    public function checkWHHoliday($date, $posId)
    {
        // warehouse non working on saturday
        if (date('l', strtotime($date)) == 'Saturday') {
            if ($this->helperDelivery->getHolidayOnSaturday($posId)) {
                return true;
            }
        }
        // warehouse non working on sunday
        if (date('l', strtotime($date)) == 'Sunday') {
            if ($this->helperDelivery->getHolidayOnSunday($posId)) {
                return true;
            }
        }
        // this day in list special list holiday of japan
        if ($this->helperDelivery->isSpecialHoliday($posId, $date)) {
            return true;
        }
        return false;
    }
}
