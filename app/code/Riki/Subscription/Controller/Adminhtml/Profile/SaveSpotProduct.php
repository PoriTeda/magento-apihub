<?php

namespace Riki\Subscription\Controller\Adminhtml\Profile;

class SaveSpotProduct extends \Magento\Backend\App\Action
{
    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfileData;

    /**
     * @var \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile
     */
    protected $profileIndexer;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subscriptionOrderHelper;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * SaveSpotProduct constructor.
     *
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfileData
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $profileIndexer
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Riki\Subscription\Helper\Profile\Data $helperProfileData,
        \Magento\Backend\App\Action\Context $context,
        \Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile $profileIndexer,
        \Riki\Subscription\Helper\Order $subscriptionOrderHelper,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
    ) {
        $this->profileIndexer = $profileIndexer;
        $this->helperProfileData = $helperProfileData;
        $this->subscriptionOrderHelper = $subscriptionOrderHelper;
        $this->subscriptionValidator = $subscriptionValidator;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $postData = $this->getRequest()->getParams();

        if (isset($postData['addSpotProduct'])) {
            /**
             * add multiple spot product
             */
            $arrSpotProductSubmit = $postData['addSpotProduct'];
            $objectDataSimulate = $this->helperProfileData->makeObjectDataForSimulate(
                $postData['profile_id'],
                $postData,
                $arrSpotProductSubmit
            );
        } else {
            /**
             * add single product
             */
            $objectDataSimulate = $this->helperProfileData->makeObjectDataForSimulate(
                $postData['profile_id'],
                $postData
            );
        }

        /* Validate before save */
        $productCarts = $objectDataSimulate->getData('product_cart');
        $validateMaximumQty = $this->subscriptionValidator->setProfileId($postData['profile_id'])
            ->setProductCarts($productCarts)
            ->validateMaximumQtyRestriction();
        if ($validateMaximumQty['error'] && !empty($validateMaximumQty['product_errors'])) {
            $message = $this->subscriptionValidator->getMessageMaximumError(
                $validateMaximumQty['product_errors'],
                $validateMaximumQty['maxQty']
            );

            $this->messageManager->addError($message);
            return $this->resultRedirectFactory->create()
                ->setPath('profile/profile/addSpotProduct', ['id' => $postData['profile_id']]);
        }

        /**
         * Validate Order Amount Restriction
         */
        $amountValidationResult = $this->subscriptionOrderHelper->validateSimulateOrderAmountRestriction(
            $objectDataSimulate['course_data'],
            $objectDataSimulate
        );
        if (!$amountValidationResult['status']) {
            $this->messageManager->addError($amountValidationResult['message']);
            return $this->resultRedirectFactory->create()
                ->setPath('profile/profile/addSpotProduct', ['id' => $postData['profile_id']]);
        }

        /**
         * Save spot product
         */
        $result = null;
        $productCartData = $objectDataSimulate->getData('product_cart');
        if (!empty($productCartData)) {
            foreach ($productCartData as $keyNewProduct => $newProductCartProfileData) {
                if (strpos($keyNewProduct, 'new_product') !== false) {
                    $result = $this->helperProfileData->saveSpotProduct($postData, $newProductCartProfileData);
                }
            }
            //clear cache
            $this->profileIndexer->removeCacheInvalid($postData['profile_id']);
        }

        if ($result) {
            $this->messageManager->addSuccess(__('Update profile successfully!'));
            $this->helperProfileData->resetProfileSession($postData['profile_id']);

            return $this->resultRedirectFactory->create()
                ->setPath('customer/index/edit', ['id' => $objectDataSimulate->getData('customer_id')]);
        } else {
            $this->messageManager->addError(__('Have error when add spot product to profile.'));

            return $this->resultRedirectFactory->create()
                ->setPath('customer/index/edit', ['id' => $objectDataSimulate->getData('customer_id')]);
        }
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::add_spot_product');
    }
}
