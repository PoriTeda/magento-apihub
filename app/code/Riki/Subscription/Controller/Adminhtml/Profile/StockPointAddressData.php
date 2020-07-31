<?php

namespace Riki\Subscription\Controller\Adminhtml\Profile;

class StockPointAddressData extends \Magento\Backend\App\Action
{
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

    protected $profileCacheRepository;

    /**
     * StockPointAddressData constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
     * @param \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->addressRepository = $addressRepository;
        $this->helperProfile = $helperProfile;
        $this->profileCacheRepository = $profileCacheRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $profileId = $this->getRequest()->getPost('profile_id');
        /* If profileId is tmp, will return main profileId else return itself*/
        $shippingAddressId = $this->getRequest()->getPost('shipping_address_id');
        $returnUrl = $this->getRequest()->getPost('return_url');
        /**
         * Validate request data
         */
        if (!$this->getRequest()->isAjax() || empty($profileId) || empty($shippingAddressId)) {
            $response = [
                'result' => false,
                'message' => __('There are something wrong in the system. Please re-try again.')
            ];
            return $this->resultJsonFactory->create()->setData($response);
        }

        $response = ['result' => false];

        if ($cacheProfile = $this->profileCacheRepository->getProfileDataCache($profileId)) {
            try {
                $address = $this->addressRepository->getById($shippingAddressId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                $response = [
                    'result' => false,
                    'message' => __('The address not found.')
                ];
                return $this->resultJsonFactory->create()->setData($response);
            }

            if ($address) {
                $fullAddress = [
                    '〒 ' . $address->getPostcode(),
                    $address->getRegion()->getRegion(),
                    trim(implode(" ", $address->getStreet()))
                ];
                $regionName = $address->getRegion()->getRegion();
                /** only used profile id main when send api */
                $mainProfileId = $this->helperProfile->getMainFromTmpProfile($profileId);
                $rawDataValue = [
                    "postcode" => $address->getPostcode(),
                    "prefecture" => $regionName,
                    "address" => implode(" ", $fullAddress),
                    "telephone" => $address->getTelephone(),
                    "return_url" => $returnUrl,
                    "magento_data" => [
                        "profile_id" => $mainProfileId,
                        'address_id'    =>  $shippingAddressId
                    ]
                ];

                try {
                    $this->buildStockPointPostData->setPostDataRequest($rawDataValue);

                    /**
                     * Set nonce data to profile
                     */
                    $nonce = $this->buildStockPointPostData->getNonceData();

                    $cacheProfile->setData('riki_stock_point_nonce', $nonce);
                    $this->profileCacheRepository->save($cacheProfile);

                    $response = [
                        'result' => true,
                        'data' => $this->buildStockPointPostData->getPostDataRequestGenerate()
                    ];
                } catch (\Exception $e) {
                    $response = [
                        'result' => false,
                        'message' => __('There are something wrong in the system. Please re-try again.')
                    ];
                }
            }
        }

        /**
         * Add message error
         */
        if (!$response['result']) {
            $response = [
                'result' => false,
                'message' => __('There are something wrong in the system. Please re-try again.')
            ];
        }
        return $this->resultJsonFactory->create()->setData($response);
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::profile_edit');
    }
}
