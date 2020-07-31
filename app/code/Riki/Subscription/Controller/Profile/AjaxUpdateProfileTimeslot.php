<?php

namespace Riki\Subscription\Controller\Profile;

use PHPUnit\Runner\Exception;

class AjaxUpdateProfileTimeslot extends \Magento\Framework\App\Action\Action
{
    const SUCCESS = 1;
    const ERR_PARAMS_INVALID = 100;
    const ERR_TIMESLOT_INVALID = 101;
    const ERR_INVALID_USER = 102;
    const ERR_UNEXPECTED = 103;
    const ERR_PARAMS_INVALID_MESSAGE = "Invalid submit data";
    const ERR_TIMESLOT_INVALID_MESSAGE = "Invalid timeslot selection";
    const ERR_INVALID_USER_MESSAGE = "Invalid user";
    const ERR_UNEXPECTED_MESSAGE = "Some error has occured. Try again";
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileData;

    /**
     * @var \Riki\DeliveryType\Model\DeliveryDate
     */
    protected $modelDeliveryDate;

    /**
     * @var
     */
    protected $customerSession;

    /**
     * AjaxUpdateProfileTimeslot constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Riki\Subscription\Helper\Profile\Data $profileData
     * @param \Riki\DeliveryType\Model\DeliveryDate $modelDeliveryDate
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Riki\DeliveryType\Model\DeliveryDate $modelDeliveryDate,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->profileData = $profileData;
        $this->modelDeliveryDate = $modelDeliveryDate;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * Update next delivery date message when customer change delivery date
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getParams();
        if(count($data) === 0){
            return null;
        }

        $response = ['code' => self::ERR_PARAMS_INVALID, 'message' => self::ERR_PARAMS_INVALID_MESSAGE];

        try {
            $profileId = $data['profileId'];
            $timeslot = $data['timeslot'];
        } catch(Exception $e){
            return $this->resultJsonFactory->create()->setData($response);
        }

        if ($profileId && $timeslot){
            if($this->customerSession->isLoggedIn()) {
                if ($this->profileData->load($profileId)->getData('customer_id') !== $this->customerSession->getCustomerId()){
                    $response = ['code' => self::ERR_INVALID_USER, 'message' => self::ERR_INVALID_USER_MESSAGE];
                    return $this->resultJsonFactory->create()->setData($response);
                }
            } else{
                $response = ['code' => self::ERR_INVALID_USER, 'message' => self::ERR_INVALID_USER_MESSAGE];
                return $this->resultJsonFactory->create()->setData($response);
            }
            $timeslotList = $this->modelDeliveryDate->getListTimeSlot();
            $availableTimeslot = false;
            foreach($timeslotList as $item){
                if($timeslot == $item['value']){
                    $availableTimeslot = true;
                    break;
                }
            }
            if($availableTimeslot === false){
                $response = ['code' => self::ERR_TIMESLOT_INVALID, 'message' => self::ERR_TIMESLOT_INVALID_MESSAGE];
                return $this->resultJsonFactory->create()->setData($response);
            }

            try {
                $profile = $this->profileData->load($profileId);
                $productCarts = $profile->getProductCart();
                foreach($productCarts as $cart){
                    $cart->setNextDeliverySlotID($timeslot);
                    $cart->save();
                }
                $response = ['code' => self::SUCCESS];
            } catch (Exception $e){
                $response = ['code' => self::ERR_UNEXPECTED, 'message' => self::ERR_UNEXPECTED_MESSAGE];
            }
        }
        return $this->resultJsonFactory->create()->setData($response);
    }
}
