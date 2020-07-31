<?php
namespace Riki\StockPoint\Model;

class StockPointValidator
{
    const API_ERROR_CODE_PROFILE_ID_REQUIRED = '1001';
    const API_ERROR_CODE_PROFILE_ID_NUMERIC = '1002';
    const API_ERROR_CODE_PROFILE_ID_NOT_EXIST = '1003';
    const API_ERROR_CODE_NEXT_DELIVERY_DATE_REQUIRED = '1004';
    const API_ERROR_CODE_NEXT_DELIVERY_DATE_INVALID = '1005';
    const API_ERROR_CODE_NEXT_DELIVERY_DATE_PAST_VALUE = '1006';
    const API_ERROR_CODE_DELIVERY_TIME_SLOT_NUMERIC = '1007';
    const API_ERROR_CODE_DELIVERY_TIME_SLOT_NOT_EXIST = '1008';
    const API_ERROR_CODE_PROFILE_ID_NOT_STOCK_POINT = '1009';
    const API_ERROR_CODE_IS_REJECT_REQUIRED = '1010';
    const API_ERROR_CODE_IS_REJECT_INVALID = '1011';
    const DEFAULT_VALUE_PARAMETER_IS_REJECT = [0,1];
    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    /**
     * @var \Riki\TimeSlots\Model\ResourceModel\TimeSlots\CollectionFactory
     */
    protected $timeslotCollectionFactory;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;

    /**
     * StockPointValidator constructor.
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Riki\TimeSlots\Model\ResourceModel\TimeSlots\CollectionFactory $collectionFactory
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     */
    public function __construct(
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\TimeSlots\Model\ResourceModel\TimeSlots\CollectionFactory $collectionFactory,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
    ) {
        $this->profileRepository = $profileRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->timeslotCollectionFactory = $collectionFactory;
        $this->dateTime = $dateTime;
    }

    /**
     * @param $profileId
     * @param $nextDeliveryDate
     * @param $deliveryTimeSlot
     * @param $isReject
     * @return bool
     */
    public function validateStopParams($profileId, $nextDeliveryDate, $deliveryTimeSlot, $isReject)
    {
        $this->validateProfileId($profileId);
        $this->validateNextDeliveryDate($nextDeliveryDate);
        $this->validateDeliveryTimeSlot($deliveryTimeSlot);
        $this->validateIsReject($isReject);
        return true;
    }

    /**
     * @param $profileId
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function validateProfileId($profileId)
    {
        if (!trim($profileId)) {
            throw new \Magento\Framework\Webapi\Exception(
                __('Profile ID is required field'),
                self::API_ERROR_CODE_PROFILE_ID_REQUIRED
            );
        }

        if (!is_numeric($profileId) || $profileId < 0) {
            throw new \Magento\Framework\Webapi\Exception(
                __('Profile ID [%1] is not an integer.', $profileId),
                self::API_ERROR_CODE_PROFILE_ID_NUMERIC
            );
        }
        $criteria = $this->searchCriteriaBuilder
            ->addFilter('profile_id', $profileId)
            ->create();
        $profileCollection = $this->profileRepository->getList($criteria);
        if (!$profileCollection->getItems()) {
            throw new \Magento\Framework\Webapi\Exception(
                __('Profile ID [%1] is not existing.', $profileId),
                self::API_ERROR_CODE_PROFILE_ID_NOT_EXIST
            );
        } else {
            foreach ($profileCollection->getItems() as $item) {
                $stockPointBucketId = $item->getData('stock_point_profile_bucket_id');
                $stockPointDeliveryType = $item->getData('stock_point_delivery_type');
                $stockPointDeliveryInformation = $item->getData('stock_point_delivery_information');
                if (!$stockPointBucketId && !$stockPointDeliveryType && !$stockPointDeliveryInformation) {
                    throw new \Magento\Framework\Webapi\Exception(
                        __('Profile ID [%1] is not a Stock Point profile.', $profileId),
                        self::API_ERROR_CODE_PROFILE_ID_NOT_STOCK_POINT
                    );
                }
            }
        }
    }

    /**
     * @param $nextDeliveryDate
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function validateNextDeliveryDate($nextDeliveryDate)
    {
        if (!trim($nextDeliveryDate)) {
            throw new \Magento\Framework\Webapi\Exception(
                __('Next Delivery Date is required field'),
                self::API_ERROR_CODE_NEXT_DELIVERY_DATE_REQUIRED
            );
        }
        if (!$this->validateDateFormat($nextDeliveryDate)) {
            throw new \Magento\Framework\Webapi\Exception(
                __('Next Delivery Date [%1] is not a date format', $nextDeliveryDate),
                self::API_ERROR_CODE_NEXT_DELIVERY_DATE_INVALID
            );
        }
        if (strtotime($nextDeliveryDate) < $this->dateTime->gmtTimestamp()) {
            throw new \Magento\Framework\Webapi\Exception(
                __('Next Delivery Date [%1] is past value.', $nextDeliveryDate),
                self::API_ERROR_CODE_NEXT_DELIVERY_DATE_PAST_VALUE
            );
        }
    }

    /**
     * @param $deliveryTimeSlot
     * @throws \Magento\Framework\Webapi\Exception
     */
    public function validateDeliveryTimeSlot($deliveryTimeSlot)
    {
        if ($deliveryTimeSlot = trim($deliveryTimeSlot)) {
            if (!is_numeric($deliveryTimeSlot) || $deliveryTimeSlot < 0) {
                throw new \Magento\Framework\Webapi\Exception(
                    __(' Delivery Time Slot [%1] is not an integer.', $deliveryTimeSlot),
                    self::API_ERROR_CODE_DELIVERY_TIME_SLOT_NUMERIC
                );
            }
            if ($deliveryTimeSlot) {
                $timeslotCollection = $this->timeslotCollectionFactory->create();
                $timeslotCollection->addFieldToFilter('id', $deliveryTimeSlot);
                if (!$timeslotCollection->getSize()) {
                    throw new \Magento\Framework\Webapi\Exception(
                        __(' Delivery Time Slot [%1] is not existed via Magento.', $deliveryTimeSlot),
                        self::API_ERROR_CODE_DELIVERY_TIME_SLOT_NOT_EXIST
                    );
                }
            }
        }
    }

    /**
     * @param $isReject
     * @throws \Magento\Framework\Webapi\Exception
     */
    private function validateIsReject($isReject)
    {
        if (!trim($isReject) && $isReject != '0') {
            throw new \Magento\Framework\Webapi\Exception(
                __('Reject option is required field'),
                self::API_ERROR_CODE_IS_REJECT_REQUIRED
            );
        }
        if (!is_numeric($isReject) || !in_array($isReject, self::DEFAULT_VALUE_PARAMETER_IS_REJECT)) {
            throw new \Magento\Framework\Webapi\Exception(
                __(' Reject value must be 0 or 1'),
                self::API_ERROR_CODE_IS_REJECT_INVALID
            );
        }
    }
    /**
     * @param $stringDate
     * @return bool
     */
    private function validateDateFormat($stringDate)
    {
        //input format : YYYY-MM-DD
        if (mb_strlen($stringDate) < 10) {
            return false;
        } else {
            $year = (int)mb_substr($stringDate, 0, 4);
            $month = (int)mb_substr($stringDate, 5, 2);
            $day = (int)mb_substr($stringDate, 8, 2);
            if (checkdate($month, $day, $year)) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * get delivery timeslot ID
     *
     * @param $entityID
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getDeliveryTimeSlotId($entityID)
    {
        $timeslotCollection = $this->timeslotCollectionFactory->create();
        $timeslotCollection->addFieldToFilter('id', $entityID);
        $timeslotCollection->setPageSize(1)->setCurPage(1);
        if ($timeslotCollection->getSize()) {
            return $timeslotCollection->getFirstItem()->getId();
        }
        else {
            throw new \Magento\Framework\Exception\NoSuchEntityException(__("Timeslot entity could not be found"));
        }
    }
}
