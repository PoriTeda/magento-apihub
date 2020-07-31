<?php

namespace Riki\Subscription\Controller\Profile;


use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;

use Riki\Subscription\Api\Simulator\CalendarInterface;

class ChangeShippingAddress extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var CalendarInterface
     */
    protected $_calendarInterface;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * ChangeShippingAddress constructor.
     * @param CalendarInterface $calendarInterface
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param Context $context
     */
    public function __construct(
        \Riki\Subscription\Api\Simulator\CalendarInterface $calendarInterface,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Action\Context $context
    )
    {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_calendarInterface = $calendarInterface;
        $this->_customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * @return $this|bool|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if ($this->_customerSession->isLoggedIn() && $this->getRequest()->getMethod() == 'POST' && $this->getRequest()->isXmlHttpRequest()) {
            $profileId = $this->getRequest()->getParam('profile_id');
            $deliveryType = $this->getRequest()->getParam('delivery_type');
            $shippingAddressId = $this->getRequest()->getParam('shipping_address');
            $delivery = [];
            if ($profileId != null && $shippingAddressId != null && $deliveryType != null) {

                $profileData = $this->_calendarInterface->checkProfileExistOnCustomer($profileId);
                if ($profileData && $profileData->getCustomerId() == $this->_customerSession->getId()) {
                    $this->_calendarInterface->setDeliveryType($deliveryType);
                    $restrictDate = $this->_calendarInterface->getRestrictCalendar($profileId, $shippingAddressId);
                    if ($restrictDate != null) {
                        $delivery = $restrictDate;
                    }
                    $resultJson = $this->_resultJsonFactory->create();
                    return $resultJson->setData([
                        'data' => \Zend_Json::encode($delivery)
                    ]);
                }
            }
        }

        //default redirect 404
        $this->_redirect('404');
    }
}