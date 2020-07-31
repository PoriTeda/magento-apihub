<?php

namespace Riki\Subscription\Controller\Profile;

class AjaxUpdateNavigationProfile extends \Magento\Framework\App\Action\Action
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

    /**
     * @var \Riki\Subscription\CustomerData\CustomerProfiles
     */
    protected $customerProfiles;

    /**
     * @var \Riki\Subscription\Model\ProfileCacheRepository
     */
    protected $profileCacheRepository;
    /**
     * @var \Riki\Subscription\Model\Profile\WebApi\ProfileRepository
     */
    private $profileRepository;

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
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository,
        \Riki\Subscription\Model\Profile\WebApi\ProfileRepository $profileRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->profileData = $profileData;
        $this->deliveryDateGenerateHelper = $deliveryDateGenerateHelper;
        $this->customerProfiles = $customerProfiles;
        $this->profileCacheRepository = $profileCacheRepository;
        $this->profileRepository = $profileRepository;
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
        $nextDeliveryDate = $this->getRequest()->getParam('next_delivery_date');
        $response = [];
        if ($nextDeliveryDate && $profileId) {
            try {
                /** @var \Riki\Subscription\Model\Profile\Profile $profileModel */
                $profileModel = $this->profileData->load($profileId, null, true);

                // save profile
                $productCarts = $profileModel->getProductCart($profileId);
                $profileModel->setData('next_delivery_date', $nextDeliveryDate);
                $productIds = [];
                $region = [];
                $arrAddress = [];
                foreach($productCarts as $productCart) {
                    $productCart->setNextDeliveryDate($nextDeliveryDate);
                    $productIds[] = $productCart->getProductId();
                    if (!isset($region[$productCart->getShippingAddressId()])) {
                        $address = $profileModel->getAddressData($productCart->getShippingAddressId());
                        $region[$productCart->getShippingAddressId()] = $address['RegionID'];
                        $arrAddress[] = $address;
                    }
                }

                $course = $profileModel->getSubscriptionCourse();
                $profileModel->setData('course_data', $course->getData());

                if ($profileModel->getData('day_of_week') != null
                    && $profileModel->getData('nth_weekday_of_month') != null) {
                    $dayOfWeek = date('l', strtotime($nextDeliveryDate));
                    $nthWeekdayOfMonth = $this->deliveryDateGenerateHelper->calculateNthWeekdayOfMonth(
                        $nextDeliveryDate
                    );

                    $profileModel->addData([
                        'day_of_week' => $dayOfWeek,
                        'nth_weekday_of_month' => $nthWeekdayOfMonth
                    ]);
                }

                $this->profileRepository->saveProfileOriginal($profileModel, $arrAddress, $region, $productIds, false);

                // remove cache
                $this->profileCacheRepository->removeCache($profileId);

                // response
                $latestProfiles = $this->customerProfiles->getSectionData();
                foreach($latestProfiles as $index=>$data){
                    $id = $data['course_id'];
                    $result = '';
                    $datepickerBlock = $this->resultPageFactory->create()
                        ->getLayout()->createBlock(\Riki\Theme\Block\Html\Header\Navigation\DeliveryDate::class);
                    $datepickerBlock->setEntity($id);
                    $datepickerBlock->setIndex($index);
                    foreach($datepickerBlock->renderDeliveryDate() as $html){
                        $result .= $html;
                    }
                    $price = $datepickerBlock->getTotalPrice();
                    $items = $datepickerBlock->getItems();
                    $calendarConfig = $datepickerBlock->getCalendarConfig();

                    $response[$id] = [
                        'html' => $result,
                        'price' => $price,
                        'items' => $items,
                        'calendarConfig' => $calendarConfig,
                        'index' => $index
                    ];
                }
            } catch (\Exception $e) {
                $response = ['result' => false, 'message' => $e->getMessage()];
            }
        }

        return $this->resultJsonFactory->create()->setData($response);
    }
}
