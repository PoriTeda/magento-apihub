<?php

namespace Riki\Theme\Block\Html\Header\Navigation;

use Riki\Subscription\Block\Frontend\Profile\Edit as Edit;

class DeliveryDate extends Edit
{
    protected $entity;

    protected $index;

    // Get total price with simulating order
    protected $totalPrice;

    // Get items with simulating order
    protected $items;

    protected $calendarConfig;

    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity($profileId){
        $objCache = $this->profileCacheRepository->initProfile($profileId, false);
        $profileCache = $objCache->getProfileData()[$profileId];
        $this->entity = $profileCache;
    }

    public function getMainProfileId()
    {
        return $this->entity->getData('profile_id');
    }

    public function setIndex($index){
        $this->index = $index;
    }

    public function removeCache($profileId){
        $this->profileCacheRepository->removeCache($profileId);
    }

    public function renderDeliveryDate(){
        // Set registry
        $objProfile = $this->_helperProfile->load($this->getMainProfileId());
        $profileCache = $this->getEntity();

        if(!is_null($this->_registry->registry('subscription_profile'))){
            $this->_registry->unregister('subscription_profile');
        }
        $this->_registry->register('subscription_profile', $profileCache);
        if(!is_null($this->_registry->registry('subscription_profile_obj'))){
            $this->_registry->unregister('subscription_profile_obj');
        }
        $this->_registry->register('subscription_profile_obj', $objProfile);

        $courseId = $objProfile->hasData('course_id') ? $objProfile->getData('course_id') : 0;
        $frequencyId = $objProfile->getSubProfileFrequencyID();

        if(!is_null($this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID))){
            $this->_registry->unregister(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID);
        }
        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $courseId);
        if(!is_null($this->_registry->registry(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID))){
            $this->_registry->unregister(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID);
        }
        $this->_registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $frequencyId);

        //
        $datepickerBlock = $this;
        $html = [];
        $courseSettings = $datepickerBlock->getCourseSetting();
        $isAllowChangeNextDelivery = $courseSettings['is_allow_change_next_delivery'];
        $isAllowChangeAddress = $courseSettings['is_allow_change_address'];
        $hanpukaiDeliveryDateAllowed = $courseSettings['hanpukai_delivery_date_allowed'];
        $hanpukaiDeliveryDateFrom = $courseSettings['hanpukai_delivery_date_from'];
        $hanpukaiDeliveryDateTo = $courseSettings['hanpukai_delivery_date_to'];
        $nextDeliveryDateCalculationOption = $courseSettings['next_delivery_date_calculation_option'];
        $isAdmin = $datepickerBlock->getIsAdmin();

        $allAddress = $datepickerBlock->getAllAddress();
        $allAddressForNewDesign = $datepickerBlock->getAllCustomerAddressDataForNewDesign();
        $allAddressDetail = [];
        $allAddressDetailForNewDesign = [];

        foreach ($allAddress as $key => $address) {
            $allAddressDetail[$key] = str_replace(',', '<br>', $address['address']);
        }

        foreach ($allAddressForNewDesign as $key => $value) {
            $allAddressDetailForNewDesign[$key] = $value;
        }
        $profile_id = $datepickerBlock->getEntity()->getProfileId();
        $frequencyUnit = $datepickerBlock->getEntity()->getData('frequency_unit');
        $frequencyInterval = $datepickerBlock->getEntity()->getData('frequency_interval');

        $isBtnUpdateAllChangesPressed = $datepickerBlock->checkBtnUpdateAllChangePressed();


        /**
         * Hanpukai
         */
        $isSubscriptionHanpukai = $datepickerBlock->isSubscriptionHanpukai();

        /*Simulator order info*/
        $simulatorOrder = $datepickerBlock->getSimulatorOrderOfProfile($profile_id);

        if($simulatorOrder == false) {
            $this->totalPrice = $this->profileFactory->create()->load($profile_id)->getTotalProductsPrice();
        }
        else {
            $this->totalPrice = $simulatorOrder->getData('base_subtotal_incl_tax');
        }

        $deliveryInformation = $datepickerBlock->getListProductByAddressAndByDeliveryType($simulatorOrder);
        $groupedDeliveryInformation = $datepickerBlock->groupDataByDeliveryType($deliveryInformation);

        $nextOfNextDeliveryDateBottom = $datepickerBlock->getMainDeliveryDateForText();
        $nextOfNextDeliveryDate = strtotime($frequencyInterval . " " . $frequencyUnit, strtotime($nextOfNextDeliveryDateBottom));
        $objNextOfNextDeliveryDate = new \DateTime();
        $objNextOfNextDeliveryDate->setTimestamp($nextOfNextDeliveryDate);

        /*Compare origin data and session data for next_delivery_date to show profile_type 1*/
        /*profile data*/
        $profileData = $datepickerBlock->getEntity();

        /* @var \Riki\StockPoint\Helper\ValidateStockPointProduct $stockPointHelper */
        $stockPointHelper = $this->validateStockPointProduct;
        $addressStockPoint = null;

        $i = 0;
        foreach ($groupedDeliveryInformation as $addressId => $arrInfoWithDL) {
            foreach ($arrInfoWithDL as $deliveryType => $arrDetailDL) {
                $stockPointExist = $stockPointHelper->checkProfileExistStockPoint($profileData);

                if ($stockPointExist) {
                    $isAllowChangeAddress = false;
                    $isAllowChangeNextDelivery = false;
                }

                $html[] = $this->getLayout()->createBlock("\Magento\Framework\View\Element\Template")->setData([
                    'arrProduct' => $arrDetailDL,
                    'isAllowChangeNextDelivery' => $isAllowChangeNextDelivery,
                    'next_delivery_date' => $datepickerBlock->getEntity()->getData('next_delivery_date'),
                    'profileId' => $datepickerBlock->getEntity()->getData('profile_id'),
                    'index' => $this->index
                ])
                    ->setData('area', 'frontend')
                    ->setTemplate("Riki_Theme::html/header/navigation/delivery_date.phtml")->toHtml();

                $index = $this->index;
                $arrProductByAddress = $arrDetailDL;
                $nextDeliveryDate = $datepickerBlock->getEntity()->getData('next_delivery_date');
                $deliveryDate = $arrProductByAddress['delivery_date']['next_delivery_date'] != null ? $arrProductByAddress['delivery_date']['next_delivery_date'] : $nextDeliveryDate;
                $deliveryDate = strtotime($frequencyInterval . " " . $frequencyUnit, strtotime($deliveryDate));

                /*profile id*/
                $profileId = $datepickerBlock->getEntity()->getData('profile_id');
                /*subscription profile data*/
                $profileData = $datepickerBlock->getEntity();

                /*Calculate Available end date*/
                $objMaxDate = $datepickerBlock->calculateAvailableEndDate();
                $maxDate = $objMaxDate->format('Y/m/d');

                if ($isSubscriptionHanpukai) {
                    if ($isAdmin && $hanpukaiDeliveryDateAllowed == 1 && $isAllowChangeNextDelivery == 1) {
                        $isDisableDatePicker = false;
                    } else {
                        $isDisableDatePicker = true;
                    }
                } else {
                    if ($isAllowChangeNextDelivery == 1) {
                        $isDisableDatePicker = false;
                    } else {
                        $isDisableDatePicker = true;
                    }
                }
                // Get Calendar Config
                if (!$isBtnUpdateAllChangesPressed) {
                    $nextDeliveryDateMain = $datepickerBlock->getNextDeliveryDateOfMain();
                    $_checkCalendar = $datepickerBlock->getHelperCalculateDateTime()->getCalendar($addressId, $arrProductByAddress, $deliveryType, null, $nextDeliveryDateMain);
                    $calendarPeriod = $datepickerBlock->getHelperCalculateDateTime()->getCalendarPeriod();
                    if (!$calendarPeriod) {
                        //set default 30days
                        $calendarPeriod = 29;
                    } else {
                        $calendarPeriod = (int)$calendarPeriod + count($_checkCalendar) - 1;
                    }
                    /*Calculate Available start date*/
                    $objMinDate = $datepickerBlock->calculateAvailableStartDate($_checkCalendar);

                    if ($objMaxDate->getTimestamp() < $objMinDate->getTimestamp()) {
                        $calendarPeriodForEdit = $datepickerBlock->getHelperCalculateDateTime()->getEditProfileCalendarPeriod() ?: 0;
                        $objMaxDate = clone $objMinDate;
                        $objMaxDate->add(new \DateInterval(sprintf('P%sD', $calendarPeriodForEdit)));
                        $maxDate = $objMaxDate->format('Y/m/d');
                    }
                }
                $this->calendarConfig = [
                    '_checkCalendar' => $_checkCalendar,
                    'hanpukaiDeliveryDateAllowed' => $hanpukaiDeliveryDateAllowed,
                    'arrDayOfWeek' => $datepickerBlock->getDayOfWeekTranslate(),
                    'objMinDate' => $objMinDate->format('Y/m/d'),
                    'maxDate' => $maxDate,
                    'profileId' => $profileId,
                    'arrNthWeekdayOfMonth' => $datepickerBlock->getNthWeekdayOfMonthTranslate(),
                    'hanpukaiDeliveryDateTo' => $hanpukaiDeliveryDateTo,
                    'isDisableDatePicker' => $isDisableDatePicker,
                ];
                break;
            }
        }
        foreach ($groupedDeliveryInformation as $addressId => $arrInfoWithDL) {
            foreach ($arrInfoWithDL as $deliveryType => $arrDetailDL) {
                $arrProduct = $arrDetailDL['product'];
                foreach ($arrProduct as $arrP) {
                    $productModel = $arrP['instance'];
                    $qty = $arrP['unit_case'] == 'CS' ? (int)$arrP['qty'] / (int)$arrP['unit_qty'] : (int)$arrP['qty'];
                    $this->items[] = [
                        'id' => $productModel->getId(),
                        'name' => $arrP['name'],
                        'qty' => $qty,
                        'thumbnail' => $this->_imageBuilder->create($productModel, 'cart_page_product_thumbnail')->getImageUrl()
                    ];
                }
            }
        }

        return $html;
    }

    public function getTotalPrice(){
        return $this->totalPrice . '円';
    }

    public function getItems(){
        return $this->items;
    }

    public function getCalendarConfig(){
        return $this->calendarConfig;
    }
}