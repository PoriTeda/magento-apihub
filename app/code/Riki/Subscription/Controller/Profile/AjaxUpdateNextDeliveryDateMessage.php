<?php

namespace Riki\Subscription\Controller\Profile;

class AjaxUpdateNextDeliveryDateMessage extends \Magento\Framework\App\Action\Action
{
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
     * @var \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper
     */
    protected $deliveryDateGenerateHelper;

    protected $customerProfiles;

    protected $profileCacheRepository;

    /**
     * AjaxUpdateNextDeliveryDateMessage constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Riki\Subscription\Helper\Profile\Data $profileData
     * @param \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper,
        \Riki\Subscription\CustomerData\CustomerProfiles $customerProfiles,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->profileData = $profileData;
        $this->deliveryDateGenerateHelper = $deliveryDateGenerateHelper;
        $this->customerProfiles = $customerProfiles;
        $this->profileCacheRepository = $profileCacheRepository;
        parent::__construct($context);
    }

    /**
     * Update next delivery date message when customer change delivery date
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $profileId = $this->getRequest()->getParam('profile_id');
        $nextDeliveryDate = $this->getRequest()->getParam('delivery_date_selected');

        $response = ['result' => false];

        if ($nextDeliveryDate && $profileId) {
            try {
                /** @var \Riki\Subscription\Model\Profile\Profile $profileModel */
                $profileModel = $this->profileData->load($profileId);
                $nextDeliveryDateDefault = trim($nextDeliveryDate);
                $dayDeliveryDate = (int)date('d', strtotime($nextDeliveryDate));

                if ($nextDeliveryDate == $profileModel->getData('next_delivery_date')
                    && $profileModel->getData('day_of_week') != null
                    && $profileModel->getData('nth_weekday_of_month') != null
                ) {
                    // Calculate next of next delivery date
                    $nextOfNextDeliveryDate = $this->profileData->_calNextDeliveryDate(
                        $profileModel->getData('next_delivery_date'),
                        $profileModel->getData('frequency_interval'),
                        $profileModel->getData('frequency_unit')
                    );

                    if ($dayDeliveryDate > 28) {
                        $nextOfNextDeliveryDate = $this->deliveryDateGenerateHelper->getLastDateOfMonth(
                            $nextOfNextDeliveryDate,
                            $nextDeliveryDateDefault
                        );
                    }

                    $dayOfWeek = $profileModel->getData('day_of_week');
                    $nthWeekdayOfMonth = $profileModel->getData('nth_weekday_of_month');
                } else {
                    // Calculate next of next delivery date
                    $nextOfNextDeliveryDate = $this->profileData->_calNextDeliveryDate(
                        $nextDeliveryDate,
                        $profileModel->getData('frequency_interval'),
                        $profileModel->getData('frequency_unit')
                    );

                    if ($dayDeliveryDate > 28) {
                        $nextOfNextDeliveryDate = $this->deliveryDateGenerateHelper->getLastDateOfMonth(
                            $nextOfNextDeliveryDate,
                            $nextDeliveryDateDefault
                        );
                    }

                    $dayOfWeek = date('l', strtotime($nextDeliveryDate));
                    $nthWeekdayOfMonth = $this->deliveryDateGenerateHelper->calculateNthWeekdayOfMonth(
                        $nextDeliveryDate
                    );
                }

                // Calculate next of next delivery date with special case
                $nextOfNextDeliveryDate = $this->deliveryDateGenerateHelper->getDeliveryDateForSpecialCase(
                    $nextOfNextDeliveryDate,
                    $dayOfWeek,
                    $nthWeekdayOfMonth
                );

                // Convert date string to date object
                $nextOfNextDeliveryDateObject = $this->deliveryDateGenerateHelper->convertDateStringToDateObject(
                    $nextOfNextDeliveryDate
                );

                $response = [
                    'result' => true,
                    'message' => __(
                        "%1-%2-%3",
                        $nextOfNextDeliveryDateObject->format('Y'),
                        $nextOfNextDeliveryDateObject->format('m'),
                        $nextOfNextDeliveryDateObject->format('d')
                    )
                ];
            } catch (\Exception $e) {
                $response = ['result' => false];
            }
        }

        return $this->resultJsonFactory->create()->setData($response);
    }
}
