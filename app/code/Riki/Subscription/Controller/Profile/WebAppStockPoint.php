<?php


namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Riki\Subscription\Helper\WebApi\DeliveryDateHelper;
use Riki\Subscription\Model\ProductCart\ProductCartFactory;
use Riki\Subscription\Model\Profile\Profile;
use Riki\Subscription\Model\Profile\ProfileFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Catalog\Model\ProductFactory;

class WebAppStockPoint extends Action implements CsrfAwareActionInterface
{
    protected $_profileData;

    protected $profileCacheRepository;
    /**
     * @var \Magento\Customer\Model\Session
     */
    private $customerSession;

    /**
     * @var \Riki\Subscription\Helper\WebApi\DeliveryDateHelper
     */
    protected $deliveryDateHelper;

    /**
     * @var \Riki\StockPoint\Api\BuildStockPointPostDataInterface
     */
    protected $buildStockPointPostData;

    /**
     * @var \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory
     */
    private $profileCollectionFactory;
    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    private $profileRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function __construct(
        Context $context,
        \Magento\Customer\Model\Session $session,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository,
        \Riki\Subscription\Helper\WebApi\DeliveryDateHelper $deliveryDateHelper,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        \Riki\Subscription\Model\Profile\ResourceModel\Profile\CollectionFactory $profileCollectionFactory,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Psr\Log\LoggerInterface $logger
    )
    {
        $this->_profileData = $profileData;
        $this->customerSession = $session;
        $this->profileCacheRepository = $profileCacheRepository;
        $this->deliveryDateHelper = $deliveryDateHelper;
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->profileRepository = $profileRepository;
        $this->profileCollectionFactory = $profileCollectionFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $params = $this->_request->getParams();
        if (!$this->customerSession->isLoggedIn()) {
            return $this->_redirect('customer/account/login');
        }
        $customerId = $this->customerSession->getCustomerId();
        if($customerId){
            $receiveData = json_decode(base64_decode($params['reqdata']), true);
            $profileId = null; $url = null;
            if (isset($receiveData['data']) && isset($receiveData['sig'])) {
                $receiveData = json_decode(base64_decode($receiveData['data']), true);
                $profileId = $receiveData['magento_data']['profile_id'];
                $url = $receiveData['magento_data']['return_url'];
            }

            if(!$url){
                return $this->_redirect('/');
            }

            $url = str_replace("\\", "", $url);

            if(!$profileId){
                return $this->redirect($url, __('Profile does not exist.'));
            }

            $customerProfileAvail = $this->checkProfileAvailable($profileId, $customerId);
            if(!$customerProfileAvail['status']){
                return $this->redirect($url, $customerProfileAvail['message']);
            }

            $objProfile = $this->_profileData->load($profileId);
            $objCache = $this->profileCacheRepository->initProfile($profileId, true, $objProfile);
            $profileCache = $objCache->getProfileData()[$profileId];
            $profileCache->setData('riki_stock_point_nonce', $this->deliveryDateHelper->getDataFromCache($profileId, DeliveryDateHelper::TYPE_NONCE));
            $validate = $this->buildStockPointPostData->validateRequestStockPoint(
                $params,
                $profileCache
            );
            if (!$validate['status']) {
                return $this->redirect($url, $validate['message']);
            }
            $this->deliveryDateHelper->saveVerificationDataToCache($profileId, null, DeliveryDateHelper::TYPE_STOCKPOINT, $params);
            $deliveryType = $receiveData['delivery_type'];
            if($deliveryType == "locker"){
                $deliveryType = Profile::LOCKER;
            } else if($deliveryType == "pickup"){
                $deliveryType = Profile::PICKUP;
            } else if($deliveryType == "dropoff"){
                $deliveryType = Profile::DROPOFF;
            } else if($deliveryType == "subcarrier"){
                $deliveryType = Profile::SUBCARRIER;
            }
            $returnParams = [
               'address_name' => $receiveData['stock_point_lastname'].$receiveData['stock_point_firstname'],
               'address_text' => implode(" ", [
                   '〒 ' . $receiveData['stock_point_postcode'],
                   $receiveData['stock_point_prefecture'],
                   $receiveData['stock_point_address']
               ]),
               'address_telephone' => $receiveData['stock_point_telephone'],
                'delivery_type' => $deliveryType
            ];
            return $this->redirect($url, null, $returnParams);
        }
        return $this->_redirect('customer/account/login');
    }

    public function redirect($url, $message = null, $data = null){
        if($message){
            $responseData = [
                'message' => $message
            ];
        } else if($data){
            $responseData = [
                'message' => null,
                'address' => $data
            ];
        }
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $url = $url . 'data=' . base64_encode(json_encode($responseData));
        return $resultRedirect->setUrl($url);
    }

    public function loadProfileList($customerId){
        $profileList = [];
        /** @var \Riki\Subscription\Model\Profile\ResourceModel\Profile\Collection $profileCollection */
        $profileCollection = $this->profileCollectionFactory->create();
        $profileCollection->addFieldToFilter('customer_id', $customerId);
        $profileCollection->addFieldToFilter('type', [
            ['neq' => Profile::SUBSCRIPTION_TYPE_TMP],
            ['null' => true]
        ])->addFieldToFilter('status', 1);
        $profileCollection->getSelect()->joinLeft(
            ['subscription_course' => 'subscription_course'],
            "main_table.course_id = subscription_course.course_id",
            [
                'subscription_course.course_name',
                'subscription_course.allow_skip_next_delivery',
                'subscription_course.allow_change_product',
                'subscription_course.is_allow_cancel_from_frontend',
                'subscription_course.minimum_order_times',
                'subscription_course.subscription_type',
            ]
        )->where(
            'subscription_course.subscription_type != ?',
            \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI
        );
        //should not display disengaged profiles
        $profileCollection->addFieldToFilter('disengagement_user', ['null' => true]);
        $profileCollection->addFieldToFilter('disengagement_date', ['null' => true]);
        $profileCollection->addFieldToFilter('disengagement_reason', ['null' => true]);
        $profileCollection->addOrder('profile_id', 'DESC');

        if($profileCollection->getSize() > 0){
            foreach($profileCollection as $profile){
                $profileList[] = $profile->getId();
            }
        }

        return $profileList;
    }

    public function customerOwnsProfile($profileId, $customerId){
        $profile = $this->profileRepository->get($profileId);
        if($profile->getCustomerId() == $customerId){
            return true;
        } else {
            return false;
        }
    }

    public function checkProfileAvailable($profileId, $customerId){
        $profileList = $this->loadProfileList($customerId);
        $response = ['status' => true];
        if(!$this->customerOwnsProfile($profileId, $customerId)){
            $response['status'] = false;
            $response['message'] = __('Customer does not own this profile');
        } else if(!in_array($profileId, $profileList)){
            $response['status'] = false;
            $response['message'] = __('This profile can\'t be edited');
        }
        return $response;
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        if ($request->getHeader('Origin')) {
            if (strpos($request->getHeader('Origin'), 'machieco.nestle.jp') !== false) {
                return true;
            }
        }
        if ($request->getHeader('Referer')) {
            if (strpos($request->getHeader('Referer'), 'machieco.nestle.jp') !== false) {
                return true;
            }
        }
        return null;
    }
}