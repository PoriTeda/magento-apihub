<?php

namespace Riki\Subscription\Controller\Adminhtml\Profile;


use Riki\Subscription\Api\Simulator\CalendarInterface;

class ChangeShippingAddress extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Session\SessionManagerInterface
     */
    protected $_sessionManager;
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_profileData;
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var CalendarInterface
     */
    protected $_calendarInterface;

    /**
     * ChangeShippingAddress constructor.
     * @param CalendarInterface $calendarInterface
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Riki\Subscription\Api\Simulator\CalendarInterface $calendarInterface,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_calendarInterface = $calendarInterface;
        $this->_sessionManager = $sessionManager;
        $this->_profileData  = $profileData;
        parent::__construct($context);
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        $profileId = $this->getRequest()->getParam('profile_id');
        $shippingAddressId = $this->getRequest()->getParam('shipping_address');
        $deliveryType = $this->getRequest()->getParam('delivery_type');
        $deliveryDate = $this->getRequest()->getParam('delivery_date');
        $timeSlotId = $this->getRequest()->getParam('time_slot_id');
        $profileCartItemIds = $this->getRequest()->getParam('profile_product_cart_id');
        $delivery = [];

        $objProfileSession = $this->getSession();
        $arrProductCartSession = $objProfileSession['product_cart'];
        foreach ($arrProductCartSession as $key => $item) {
            if (in_array($item->getData('cart_id'), $profileCartItemIds)) {
                if ($shippingAddressId) {
                    $item->setData('shipping_address_id', $shippingAddressId);
                }
                if ($deliveryDate) {
                    $item->setData('delivery_date', $deliveryDate);
                }
                if ($timeSlotId) {
                    $item->setData('delivery_time_slot', $timeSlotId);
                }
            }
        }
        $objProfileSession['product_cart'] = $arrProductCartSession;
        $objProfileSession[\Riki\Subscription\Model\Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED] = true;
        $this->getSession()->setData(\Riki\Subscription\Model\Constant::SESSION_PROFILE_EDIT, $objProfileSession);

        if ($profileId != null && $shippingAddressId != null && $deliveryType != null) {
            $this->_calendarInterface->setDeliveryType($deliveryType);
            $restrictDate = $this->_calendarInterface->getRestrictCalendar($profileId, $shippingAddressId);
            if ($restrictDate !=null) {
                $delivery = $restrictDate;
            }
        }

        $resultJson = $this->_resultJsonFactory->create();
        return $resultJson->setData([
            'data' => json_encode($delivery)
        ]);
    }

    protected function getSession()
    {
        $profileId = $this->getRequest()->getParam('profile_id');
        if ($this->_profileData->getTmpProfile($profileId) !== false) {
            $profileId = $this->_profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        if ($sessionWrapper = $this->_sessionManager->getProfileData()) {
            if (is_array($sessionWrapper) and isset($sessionWrapper[$profileId])) {
                return $sessionWrapper[$profileId];
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * Allow all request get data
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}