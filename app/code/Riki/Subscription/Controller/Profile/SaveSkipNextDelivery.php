<?php

namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\Exception\NoSuchEntityException;
use Symfony\Component\Config\Definition\Exception\Exception;

class SaveSkipNextDelivery extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileRepository
     */
    protected $profileRepository;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $profileData;

    /* @var \Magento\Framework\Data\Form\FormKey\Validator */
    protected $_formKeyValidator;
    /**
     * @var \Riki\Subscription\Model\ProductCart\ProfileProductCartRepository
     */
    protected $productCartRepository;
    /**
     * @var \Riki\Subscription\Model\ProductCart\ProductCartFactory
     */
    protected $productCartFactory;

    /**
     * @var \Riki\SubscriptionCourse\Model\Course
     */
    private $course;

    /**
     * @var \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper
     */
    protected $deliveryDateGenerateHelper;

    public function __construct(
        \Magento\Framework\Data\Form\FormKey\Validator $validator,
        \Magento\Framework\App\Action\Context $context,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Riki\Subscription\Model\Profile\ProfileRepository $profileRepositoryInterface,
        \Riki\Subscription\Model\ProductCart\ProfileProductCartRepository $productCartRepository,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory,
        \Riki\SubscriptionCourse\Model\Course $course,
        \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper
    ){
        $this->_formKeyValidator = $validator;
        $this->profileData = $profileData;
        $this->profileRepository = $profileRepositoryInterface;
        $this->productCartRepository = $productCartRepository;
        $this->productCartFactory = $productCartFactory;
        $this->course = $course;
        $this->deliveryDateGenerateHelper = $deliveryDateGenerateHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $redirectUrl = null;
        if (!$this->_formKeyValidator->validate($this->getRequest()) || !$this->getRequest()->isPost()) {
            $this->messageManager->addError(__('Sorry, we could not save your modification because of the session time out. Please re-login and try it again.'));
            return $this->resultRedirectFactory->create()->setPath($this->_redirect->getRefererUrl());
        }
        try {
            $profileId = $this->getRequest()->getParam('profile_id');
            if (!$profileId || !$this->profileData->load($profileId)) {
                $this->messageManager->addError(__('Profile Not Exist'));
                $this->_redirect('*/*');
            }
            $version = $this->profileData->checkProfileHaveVersion($profileId);
            /*Skip case: profile does not have any version*/
            if(!$version)
            {
                /*Skip case: profile have a tmp*/
                if($tmp = $this->profileData->getTmpProfile($profileId)){
                    $this->profileData->deleteTmpProfile($tmp);

                    $profileModel = $this->profileRepository->get($profileId);

                    $nextDeliveryDate = $profileModel->getNextDeliveryDate();
                    $frequencyInterval = $profileModel->getFrequencyInterval();
                    $frequencyUnit = $profileModel->getFrequencyUnit();
                    $updateNextDeliveryDate = $this->_calNextDeliveryDate(strtotime($nextDeliveryDate),$frequencyInterval,$frequencyUnit+2);
                    $this->profileData->makeNewTmpSubscriptionProfile($profileModel,$updateNextDeliveryDate,2);
                }
                else{/*Skip case: profile does not have a tmp*/
                    $this->updateOriginalProfileToNext($profileId);
                }
            }else /*Skip case: profile have version but does not have tmp*/
            {
                /*Skip case: profile have a tmp*/
                if($tmp = $this->profileData->getTmpProfile($profileId)){
                    $this->profileData->deleteTmpProfile($tmp);
                    $profileModel = $this->profileRepository->get($profileId);
                    if($profileModel->getProfileId()) {
                        $nextDeliveryDate = $profileModel->getNextDeliveryDate();
                        $frequencyInterval = $profileModel->getFrequencyInterval();
                        $frequencyUnit = $profileModel->getFrequencyUnit();
                        $updateNextDeliveryDate = $this->_calNextDeliveryDate(strtotime($nextDeliveryDate),$frequencyInterval,$frequencyUnit+2);
                        $this->profileData->makeNewTmpSubscriptionProfile($profileModel,$updateNextDeliveryDate,2);
                    }
                }
                else{/*Skip case: profile does not have a tmp*/
                    /*1: expired version*/
                    $this->profileData->expiredVersion($profileId);
                    $this->updateOriginalProfileToNext($profileId);
                }
            }
            $this->messageManager->addSuccess(__('Update profile successfully!'));
            $this->profileData->resetProfileSession($profileId);
            return $this->_redirect('*/*/index');
        } catch(\Exception $e) {
            $this->messageManager->addError('Not Save Profile');
            return $this->_redirect('*/*/index');
        }
    }

    /**
     * Get next delivery date
     *
     * @param $time
     * @param $frequencyInterval
     * @param $strFrequencyUnit
     * @return string
     */
    private function _calNextDeliveryDate($time, $frequencyInterval, $strFrequencyUnit)
    {

        $timestamp = strtotime($frequencyInterval . " " . $strFrequencyUnit, $time);

        $objDate  = new \DateTime();
        $objDate->setTimestamp($timestamp);

        return $objDate->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
    }

    /**
     * Update product cart
     *
     * @param $profileId
     * @param $frequencyInterval
     * @param $frequencyUnit
     * @param $profileModel
     * @param $nextDeliveryDate
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function updateDeliveryDateForProductCart(
        $profileId,
        $frequencyInterval,
        $frequencyUnit,
        $profileModel,
        $nextDeliveryDate
    ) {
        $productCartModel = $this->profileRepository->getListProductCart($profileId);
        foreach ($productCartModel->getItems() as $productCartItem){
            $deliveryDate = $productCartItem->getDeliveryDate();
            $nextDeliveryDateProductCart = $this->_calNextDeliveryDate(strtotime($deliveryDate),$frequencyInterval,$frequencyUnit);

            // NED-638: Update the next delivery date of product cart
            // If subscription profile has day_of_week is not null and nth_weekday_of_month is not null
            if ($profileModel->getDayOfWeek() != null
                && $profileModel->getNthWeekdayOfMonth() != null
            ) {
                $nextDeliveryDateProductCart = $nextDeliveryDate;
            }

            $productCartItem->setDeliveryDate($nextDeliveryDateProductCart);
            $productCartItem->save();
        }
    }

    /**
     * Update Original profile when skip next delivery
     * 
     * @param $profileId
     */
    public function updateOriginalProfileToNext($profileId){
        $profileModel = $this->profileRepository->get($profileId);
        $nextDeliveryDate =  $profileModel->getNextDeliveryDate();
        $frequencyInterval = $profileModel->getFrequencyInterval();
        $frequencyUnit = $profileModel->getFrequencyUnit();
        $dayOfWeek = $nthWeekdayOfMonth = null;
        /*Skip: + 1 frequency*/
        $nextDeliveryDate = $this->_calNextDeliveryDate(strtotime($nextDeliveryDate),$frequencyInterval,$frequencyUnit);

        // NED-638: Calculation of the next delivery date
        // If subscription course setup with Next Delivery Date Calculation Option = "day of the week"
        // AND interval_unit="month"
        // AND not Stock Point
        if ($profileModel->getFrequencyUnit() == 'month') {
            $this->course->load($profileModel->getCourseId());
            if ($this->course->getData('next_delivery_date_calculation_option')
                == \Riki\SubscriptionCourse\Model\Course::NEXT_DELIVERY_DATE_CALCULATION_OPTION_DAY_OF_WEEK
                && !$profileModel->getStockPointProfileBucketId()
            ) {
                if ($profileModel->getDayOfWeek() != null
                    && $profileModel->getNthWeekdayOfMonth() != null
                ) {
                    $dayOfWeek = $profileModel->getDayOfWeek();
                    $nthWeekdayOfMonth = $profileModel->getNthWeekdayOfMonth();
                } else {
                    $dayOfWeek = date('l', strtotime($profileModel->getNextDeliveryDate()));
                    $nthWeekdayOfMonth = $this->deliveryDateGenerateHelper->calculateNthWeekdayOfMonth(
                        $profileModel->getNextDeliveryDate()
                    );
                }

                $nextDeliveryDate = $this->deliveryDateGenerateHelper->getDeliveryDateForSpecialCase(
                    $nextDeliveryDate,
                    $dayOfWeek,
                    $nthWeekdayOfMonth
                );
            }
        }

        $nextOrderDate = $this->profileData->calculatorNextOrderDateFromProfile($nextDeliveryDate,$profileId);

        $profileModel->setNextDeliveryDate($nextDeliveryDate);
        $profileModel->setNextOrderDate($nextOrderDate);
        $profileModel->setDayOfWeek($dayOfWeek);
        $profileModel->setNthWeekdayOfMonth($nthWeekdayOfMonth);

        $this->profileRepository->save($profileModel);
        $this->updateDeliveryDateForProductCart($profileId, $frequencyInterval, $frequencyUnit, $profileModel, $nextDeliveryDate);
    }
}