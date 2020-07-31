<?php

namespace Riki\Subscription\Controller\Profile;

class AjaxFirstRenderCalendar extends \Magento\Framework\App\Action\Action
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
        $index = $this->getRequest()->getParam('index');
        $response = ['result' => false];
        $result = '';
        if (isset($profileId) && isset($index)) {
            try {
                /** @var \Riki\Subscription\Model\Profile\Profile $profileModel */
                $datepickerBlock = $this->resultPageFactory->create()
                    ->getLayout()->createBlock('\Riki\Theme\Block\Html\Header\Navigation\DeliveryDate');
                $datepickerBlock->removeCache($profileId);
                $datepickerBlock->setEntity($profileId);
                $datepickerBlock->setIndex($index);
                foreach ($datepickerBlock->renderDeliveryDate() as $html) {
                    $result .= $html;
                }
                $response = [
                    'result' => true,
                    'html' => $result,
                    // get price after simulating order
                    'price' => $datepickerBlock->getTotalPrice(),
                    // get items after simulating order
                    'items' => $datepickerBlock->getItems(),
                    'calendarConfig' => $datepickerBlock->getCalendarConfig()
                    ];
            } catch (\Exception $e) {
                $response = ['result' => false, 'message' => $e->getMessage()];
            }
        }

        return $this->resultJsonFactory->create()->setData($response);
    }
}
