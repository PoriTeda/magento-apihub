<?php

namespace Riki\Subscription\Controller\Profile;

use Magento\Customer\Model\Session as CustomerSession;

class RemoveStockPoint extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileData;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Riki\Subscription\Model\ProfileCacheRepository
     */
    protected $profileCacheRepository;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManagerInterface;

    /**
     * RemoveStockPoint constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param CustomerSession $customerSession
     * @param \Riki\Subscription\Helper\Profile\Data $subHelperProfile
     * @param \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        \Riki\Subscription\Helper\Profile\Data $subHelperProfile,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->profileData = $subHelperProfile;
        $this->profileCacheRepository = $profileCacheRepository;
        $this->messageManagerInterface = $context->getMessageManager();
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|
     * \Magento\Framework\Controller\Result\Json|
     * \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $profileId = $this->getRequest()->getPost('profile_id');

        /**
         * Validate request data
         */
        if (!$this->getRequest()->isAjax() || empty($profileId)) {
            $this->_redirect('404');
        }

        $response = ['success' => 'false'];
        try {
            if ($this->profileData->getTmpProfile($profileId) !== false) {
                $profileId = $this->profileData->getTmpProfile($profileId)->getData('linked_profile_id');
            }
            $profile = $this->profileCacheRepository->getProfileDataCache($profileId);
            $customerId = $this->getCustomerId();

            if ($profile && $customerId == $profile->getData('customer_id')) {
                $hasBucketId = $profile->getData('stock_point_profile_bucket_id');
                $profile->setData("stock_point_profile_bucket_id", null)
                    ->setData("stock_point_delivery_type", null)
                    ->setData("stock_point_delivery_information", null)
                    ->setData("stock_point_data", null)
                    ->setData("riki_stock_point_id", null)
                    ->setData("is_delete_stock_point", true)
                    ->setData("delete_profile_has_bucket_id", $hasBucketId);

                $addressBeforeChange = $profile->getData("riki_shipping_address_before_change");
                foreach ($profile['product_cart'] as $productId => $product) {
                    $profile['product_cart'][$productId]->setData(
                        'stock_point_discount_rate',
                        0
                    );
                    if ($addressBeforeChange) {
                        $profile['product_cart'][$productId]->setData(
                            'shipping_address_id',
                            $addressBeforeChange
                        );
                    }
                }
                $response = [
                    'success' => 'true'
                ];
                $this->profileCacheRepository->save($profile);
            } else {
                $this->messageManagerInterface->addErrorMessage(
                    __('There are something wrong in the system. Please re-try again.')
                );
            }
        } catch (\Exception $e) {
            $this->messageManagerInterface->addErrorMessage(
                __('There are something wrong in the system. Please re-try again.')
            );
        }

        return $this->resultJsonFactory->create()->setData($response);
    }

    /**
     * @return int|null
     */
    protected function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }
}
