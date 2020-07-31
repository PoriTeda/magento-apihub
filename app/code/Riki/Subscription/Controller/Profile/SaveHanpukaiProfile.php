<?php

namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\DataObject;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Riki\Subscription\Model\Constant;

class SaveHanpukaiProfile extends \Magento\Framework\App\Action\Action
{

    /* @var \Riki\Subscription\Helper\Profile\Controller\Save */
    protected $_saveHelper;

    /* @var \Riki\SubscriptionCourse\Model\CourseFactory */
    protected $courseFactory;

    /* @var \Riki\Subscription\Model\ProductCart\ProductCartFactory $collectionProductCart */
    protected $productCartCollection;

    /* @var \Magento\Catalog\Api\ProductRepositoryInterface */
    protected $productRepository;

    /* @var \Magento\Framework\Api\Filter */
    protected $filter;

    /* @var \Magento\Framework\Api\SearchCriteriaInterface */
    protected $searchCriteriaInterface;

    /* @var \Magento\Framework\Api\Search\FilterGroup */
    protected $filterGroup;

    /* @var \Riki\Subscription\Model\Frequency\Frequency */
    protected $frequencyFactory;

    /* @var \Magento\Framework\Data\Form\FormKey\Validator */
    protected $_formKeyValidator;

    /* @var \Magento\Framework\UrlInterface */
    protected $_url;

    public function __construct(
        \Magento\Framework\Data\Form\FormKey\Validator $validator,
        \Magento\Framework\Api\Search\FilterGroup $filterGroup,
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteriaInterface,
        \Magento\Framework\Api\Filter $filter,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryInterface,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Framework\App\Action\Context $context,
        \Riki\Subscription\Helper\Profile\Controller\Save $saveHelper
    ){
        parent::__construct($context);
        $this->_url = $context->getUrl();
        $this->_formKeyValidator = $validator;
        $this->filterGroup = $filterGroup;
        $this->searchCriteriaInterface = $searchCriteriaInterface;
        $this->filter = $filter;
        $this->productRepository = $productRepositoryInterface;
        $this->_saveHelper = $saveHelper;
        $this->courseFactory = $courseFactory;
        $this->productCartCollection = $productCartFactory;
    }

    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest()) || !$this->getRequest()->isPost()) {
            $this->messageManager->addError('Something error. Please try again.');
            $referUrl = $this->_redirect->getRefererUrl();
            return $this->resultRedirectFactory->create()->setPath($referUrl);
        }

        $changeType = $this->getRequest()->getParam('profile_type');
        $profileId = $this->getRequest()->getParam('profile_id');
        $profileHelperData = $this->_saveHelper->getProfileHelperData();
        $objProfile = $profileHelperData->loadProfileModel($profileId);
        $objProfileSession = $this->makeObjProfileSession($objProfile, $profileId);
        // correct data before save
        $arrParams = array();

        $paymentMethod =  $this->getPaymentMethod($objProfileSession);
        if (empty($paymentMethod)) {
            $this->messageManager->addError(__('Payment method is null'));
            $referUrl = $this->_redirect->getRefererUrl();
            return $this->resultRedirectFactory->create()->setPath($referUrl);
        }
        $arrParams['payment_method'] = $paymentMethod;
        if ($this->getRequest()->getParam('preferred_payment_method')) {
            $arrParams['save_prederred'] = 1;
        } else {
            $arrParams['save_prederred'] = '';
        }
        if($paymentMethod == 'new_paygent') {
            $paymentMethod = 'paygent';
            $objProfileSession->setData('is_new_paygent_method', true);
        } else {
            $objProfileSession->setData('is_new_paygent_method', false);
        }
        $objProfileSession->setData('edit_hanpukai', 1);
        $objProfileSession->setData("frequency_unit", $objProfile->getData("frequency_unit"));
        $objProfileSession->setData("frequency_interval", $objProfile->getData("frequency_interval"));
        $objProfileSession->setData("payment_method", $paymentMethod);
        $objProfileSession->setData('updated_at', (new \DateTime())->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT));
        $objProfileSession->setData('profile_type', $changeType);

        //set coupon code
        $couponCode = $this->getRequest()->getParam('coupon_code');
        $objProfileSession->setData('coupon_code', $couponCode);

        $redirectResult = $this->_saveHelper->confirmedAllChange($profileId, $objProfile, $objProfileSession, false, $paymentMethod, $this->messageManager, $arrParams);
        if($redirectResult === true){
            //$this->messageManager->addSuccess(__("Update profile successfully!"));
            return $this->_redirect($this->_url->getUrl('subscriptions/profile/editSuccess/'));
        }
        else {
            return $this->_redirect($redirectResult);
        }
    }

    /**
     * Make object profile session to use current save profile function
     *
     * @param $profileModel
     *
     * @return object
     */
    public function makeObjProfileSession($profileModel, $profileId)
    {
        $profileSessionObject = new DataObject();
        $profileSessionObject->setData($profileModel->getData());
        $courseData = $this->courseFactory->create()->load($profileModel->getData('course_id'));
        $profileSessionObject->setData('course_data', $courseData->getData());
        $productCart = $this->productCartCollection->create()->getCollection()
            ->addFieldToFilter('profile_id', $profileModel->getData('profile_id'));
        $profileSessionObject->setData('product_cart', $this->makeProductCartCorrectType($productCart));
        $addressData = $this->makeAddressData($productCart, $profileId);
        $profileSessionObject->setData('address', $addressData);
        // Need set profile type and change payment method
        return $profileSessionObject;
    }

    /**
     * Make Product Cart
     *
     * @param $productCartCollection
     */
    public function makeProductCartCorrectType($productCartCollection)
    {
        $arrResult = array();
        foreach ($productCartCollection as $productCartItem) {
            $arrResult[$productCartItem->getData('cart_id')] = $productCartItem;
        }
        return $arrResult;
    }

    /**
     * Make address data
     *
     * @param $productCartObject
     *
     * @return array
     */
    public function makeAddressData($productCartObject, $profileId)
    {
        $arrResult = array();
        $allProductId = $this->productCartCollection->create()
            ->getCollection()->addFieldToSelect('product_id')
            ->addFieldToFilter('profile_id', $profileId)
            ->addFieldToFilter('parent_item_id', 0);
        $filters[] = $this->filter->setField('entity_id')->setConditionType('in')->setValue($allProductId->getData());
        $filterGroup[]  = $this->filterGroup->setFilters($filters);
        $searchCriteria = $this->searchCriteriaInterface->setFilterGroups($filterGroup);
        $searchResult = $this->productRepository->getList($searchCriteria);
        $arrProductIdProductObj = array();
        foreach ($searchResult->getItems() as $productObj) {
            $arrProductIdProductObj[$productObj->getData('entity_id')] = $productObj;
        }
        foreach ($productCartObject as $productCartItem) {
            if (array_key_exists($productCartItem->getData('product_id'), $arrProductIdProductObj)) {
                $productObj = $arrProductIdProductObj[$productCartItem->getData('product_id')];
                $arrResult[$productCartItem->getData('shipping_address_id')][$productObj->getDeliveryType()]
                    = $productCartItem->getData('shipping_address_id');
            }
        }
        return $arrResult;
    }

    /**
     * Get value of payment method
     *
     * @param $objProfileSession
     * @return mixed
     */
    public function getPaymentMethod($objProfileSession)
    {
        $paymentMethod = $this->getRequest()->getParam('payment_method');
        if ($objProfileSession) {
            $course = $objProfileSession->getData('course_data');
            if (isset($course['allow_change_payment_method']) && $course['allow_change_payment_method'] == 0) {
                $paymentMethod = $objProfileSession->getPaymentMethod();
            }
        }
        return $paymentMethod;
    }
}