<?php

namespace Riki\Subscription\Controller\Profile;

use Magento\Customer\Model\Session as CustomerSession;

class StockPointAddressData extends \Magento\Framework\App\Action\Action
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
     * @var \Riki\StockPoint\Api\BuildStockPointPostDataInterface
     */
    protected $buildStockPointPostData;

    /**
     * @var \Magento\Customer\Api\AddressRepositoryInterface
     */
    protected $addressRepository;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;
    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlInterface;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManagerInterface;

    /**
     * @var \Riki\Subscription\Model\ProfileCacheRepository
     */
    protected $profileCacheRepository;

    /**
     * StockPointAddressData constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param CustomerSession $customerSession
     * @param \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        CustomerSession $customerSession,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->addressRepository = $addressRepository;
        $this->helperProfile = $helperProfile;
        $this->urlInterface = $context->getUrl();
        $this->messageManagerInterface = $context->getMessageManager();
        $this->profileCacheRepository = $profileCacheRepository;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $profileId = $this->getRequest()->getPost('profile_id');
        /* If profileId is tmp, will return main profileId else return itself*/
        $returnProfile = $this->helperProfile->getMainFromTmpProfile($profileId);
        $shippingAddressId = $this->getRequest()->getPost('shipping_address_id');

        /**
         * Validate request data
         */
        if (!$this->getRequest()->isAjax() || empty($profileId) || empty($shippingAddressId)) {
            $this->_redirect('404');
        }

        $response = ['result' => false];

        if ($profile = $this->profileCacheRepository->getProfileDataCache($profileId)) {
            $customerId = $this->getCustomerId();
            $address = $this->addressRepository->getById($shippingAddressId);
            if ($profile && $customerId == $profile->getData('customer_id') && $address) {
                $fullAddress = [
                    '〒 ' . $address->getPostcode(),
                    $address->getRegion()->getRegion(),
                    trim(implode(" ", $address->getStreet()))
                ];
                $regionName = $address->getRegion()->getRegion();
                $rawDataValue = [
                    "postcode" => $address->getPostcode(),
                    "prefecture" => $regionName,
                    "address" => implode(" ", $fullAddress),
                    "telephone" => $address->getTelephone(),
                    "return_url" => $this->urlInterface->getUrl('subscriptions/profile/edit', [
                            'id'=>$returnProfile
                        ]),
                    "magento_data" => [
                        "profile_id" => $returnProfile
                    ]
                ];

                try {
                    $this->buildStockPointPostData->setPostDataRequest($rawDataValue);

                    /**
                     * Set nonce data to profile
                     */
                    $nonce = $this->buildStockPointPostData->getNonceData();
                    if (isset($profile)) {
                        $profile->setData('riki_stock_point_nonce', $nonce);
                        $this->processShippingAddressStockPoint($shippingAddressId, $profile);
                    }
                    $response = [
                        'result' => true,
                        'data' => $this->buildStockPointPostData->getPostDataRequestGenerate()
                    ];
                    $this->profileCacheRepository->save($profile);
                } catch (\Exception $e) {
                    $response = ['result' => false];
                }
            }
        }

        /**
         * Add message error
         */
        if (!$response['result']) {
            $this->messageManagerInterface->addErrorMessage(
                __('There are something wrong in the system. Please re-try again.')
            );
        }
        return $this->resultJsonFactory->create()->setData($response);
    }

    /**
     * Retrieve customer data object
     *
     * @return int
     */
    protected function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

    /**
     * Process shipping address for stock point
     *
     * @param $profileId
     * @param $shippingAddressId
     */
    public function processShippingAddressStockPoint($shippingAddressId, $profile)
    {
        $productCart = $profile->getData('product_cart');
        if (!empty($productCart)) {
            $shippingAddressBeforeChange = null;
            foreach ($productCart as $key => $item) {
                $shippingAddressBeforeChange = $productCart[$key]['shipping_address_id'];
                $productCart[$key]['shipping_address_id'] = $shippingAddressId;
            }
            $profile->setData('product_cart', $productCart);
            $profile->setData(
                'riki_shipping_address_before_change',
                $shippingAddressBeforeChange
            );
        }
    }
}
