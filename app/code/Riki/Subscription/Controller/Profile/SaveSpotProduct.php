<?php

namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Riki\Subscription\Helper\Profile\Data as ProfileHelper;
use Riki\Subscription\Model\Profile\ResourceModel\Indexer\Profile as IndexerProfile;

class SaveSpotProduct extends \Magento\Framework\App\Action\Action
{
    /**
     * @var FormKeyValidator
     */
    protected $_formKeyValidator;

    /**
     * @var ProfileHelper
     */
    protected $_profileHelper;

    /**
     * @var IndexerProfile
     */
    protected $_profileIndexer;

    /**
     * @var \Riki\StockPoint\Api\BuildStockPointPostDataInterface
     */
    protected $buildStockPointPostData;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Riki\StockPoint\Helper\ValidateStockPointProduct
     */
    protected $validateStockPointProduct;

    /**
     * @var \Riki\Framework\Helper\Transaction\Database
     */
    protected $dbTransaction;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subscriptionOrderHelper;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * @var \Riki\Customer\Model\SsoConfig
     */
    protected $ssoConfig;

    /**
     * SaveSpotProduct constructor.
     * @param Context $context
     * @param FormKeyValidator $formkeyValidator
     * @param ProfileHelper $profileHelper
     * @param IndexerProfile $profileIndexer
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData
     * @param \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct
     * @param \Riki\Framework\Helper\Transaction\Database $dbTransaction
     * @param \Riki\Subscription\Helper\Order $subscriptionOrderHelper
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     * @param \Riki\Customer\Model\SsoConfig $ssoConfig
     */
    public function __construct(
        Context $context,
        FormKeyValidator $formkeyValidator,
        ProfileHelper $profileHelper,
        IndexerProfile $profileIndexer,
        \Magento\Customer\Model\Session $customerSession,
        \Riki\StockPoint\Api\BuildStockPointPostDataInterface $buildStockPointPostData,
        \Riki\StockPoint\Helper\ValidateStockPointProduct $validateStockPointProduct,
        \Riki\Framework\Helper\Transaction\Database $dbTransaction,
        \Riki\Subscription\Helper\Order $subscriptionOrderHelper,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator,
        \Riki\Customer\Model\SsoConfig $ssoConfig
    ) {
        $this->_formKeyValidator = $formkeyValidator;
        $this->_profileHelper = $profileHelper;
        $this->_profileIndexer = $profileIndexer;
        $this->customerSession = $customerSession;
        $this->buildStockPointPostData = $buildStockPointPostData;
        $this->validateStockPointProduct = $validateStockPointProduct;
        $this->dbTransaction = $dbTransaction;
        $this->subscriptionOrderHelper = $subscriptionOrderHelper;
        $this->subscriptionValidator = $subscriptionValidator;
        $this->ssoConfig = $ssoConfig;
        parent::__construct($context);
    }

    public function execute()
    {
        $redirectUrl = null;
        if (!$this->_formKeyValidator->validate($this->getRequest()) || !$this->getRequest()->isPost()) {
            $this->messageManager->addError(__('Have error when save date'));
            return $this->resultRedirectFactory->create()->setPath($this->_redirect->getRefererUrl());
        }

        /**
         * Load data form post
         */
        $param = $this->getRequest()->getParams();
        $mainProfileId = $param['profile_id'];

        /**
         * Check main profile id
         */
        if ($this->_profileHelper->isTmpProfileId($mainProfileId)) {
            return $this->_redirect('*/*');
        }

        /**
         * Check custom exist on current profile
         */
        $customerId = $this->customerSession->getCustomerId();
        $validate = $this->_profileHelper->checkProfileBelongToCustomer($mainProfileId, $customerId);
        if (!$validate) {
            return $this->_redirect('*/*');
        }

        /**
         * Build object data simulate for save data stock point
         */
        $dataSimulate = $this->_profileHelper->makeObjectDataForSimulate($mainProfileId, $param);
        $productCartData = $dataSimulate->getData('product_cart');
        $newProductData = $productCartData['new_product'];

        /** Validate maximum qty restriction */
        $prepareData = $this->_profileHelper->prepareDataForValidateMaximumQty($productCartData);
        $validateMaximumQty = $this->subscriptionValidator->setProfileId($mainProfileId)
            ->setProductCarts($prepareData)
            ->validateMaximumQtyRestriction();
        if ($validateMaximumQty['error'] && !empty($validateMaximumQty['product_errors'])) {
            $message = $this->subscriptionValidator->getMessageMaximumError(
                $validateMaximumQty['product_errors'],
                $validateMaximumQty['maxQty']
            );
            $this->messageManager->addErrorMessage($message);
            return $this->resultRedirectFactory->create()->setPath($this->_redirect->getRefererUrl());
        }

        /**
         * Check allow stock point carrier
         */
        $product = $this->_profileHelper->loadProductById($newProductData['product_id']);
        $profile = $this->_profileHelper->loadProfileModel($newProductData->getProfileId());
        $profileExistSP = $this->validateStockPointProduct->checkProfileExistStockPoint($profile);
        if ($profileExistSP) {
            if (!$this->validateStockPointBeforeSave($profile, $product, $newProductData->getQty())) {
                $this->messageManager->addError(__('The selected product is not allowed to buy with Stock Point.'));
                return $this->_redirect('*/*');
            }
        }

        /**
         * Validate Order Amount Restriction
         */
        $resultValidateAmount = $this->subscriptionOrderHelper->validateSimulateOrderAmountRestriction(
            $dataSimulate['course_data'],
            $dataSimulate
        );

        if (!$resultValidateAmount['status']) {
            $this->messageManager->addError($resultValidateAmount['message']);
            return $this->_redirect('*/*/');
        }

        $this->dbTransaction->beginTransaction();
        $result = $this->_profileHelper->saveSpotProduct($param, $newProductData);
        if ($result) {
            /**
             * Call api remove stock point.If fail ,it will roll back data
             */
            if ($profile && $profileExistSP) {
                $profileCarrier = $this->validateStockPointProduct->checkProfileStockPointSubCarrier($profile);
                $isCleanDataCarrier = $this->validateStockPointProduct->canCleanDataSpCarrier();
                if ($profileCarrier && $isCleanDataCarrier) {
                    try {
                        $result = $this->removeProfileStockPointSubCarrier($mainProfileId, $profile);
                        if ($result) {
                            $this->dbTransaction->commit();
                        } else {
                            $this->messageManager->addError(
                                __("There are something wrong in the system. Please re-try again.")
                            );
                            $this->dbTransaction->rollback();
                        }
                    } catch (\Exception $e) {
                        $this->dbTransaction->rollback();
                    }
                } else {
                    /**
                     * For stock point type different subcarrier
                     */
                    $this->dbTransaction->commit();
                }
            } else {
                /**
                 * For profile not stock point
                 */
                $this->dbTransaction->commit();
            }

            $this->_profileIndexer->removeCacheInvalid($param['profile_id']);
            $this->messageManager->addSuccess(__('Update profile successfully!'));
            $this->_profileHelper->resetProfileSession($mainProfileId);
            if ($this->ssoConfig->isEnabledApp()) {
                header('location: ' . $this->ssoConfig->getUrlApp() . 'ec/subscriptions/profile/edit/id/'. $mainProfileId);
                return;
            } else {
                return $this->_redirect('*/*/');
            }
        } else {
            $this->messageManager->addError(
                __("I'm sorry. We were unable to accept changes. Sorry to trouble you, please try again or contact Nestlé Mail Order Call Center (0120-600-868).")
            );
            return $this->_redirect('*/*/');
        }
    }

    /**
     * Remove stock point profile subcarrier
     * @param $mainProfileId
     * @param $profile
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function removeProfileStockPointSubCarrier($mainProfileId, $profile)
    {
        if ($profile) {
            $resultApi = $this->buildStockPointPostData->removeFromBucket($mainProfileId);
            if (isset($resultApi['success']) && $resultApi['success']) {
                $result = $this->_profileHelper->cleanDataProfileStockPoint($profile);
                if ($result) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Validate product for stock point before save to profile
     *
     * @param $profile
     * @param $product
     * @param $productQty
     * @return bool
     */
    protected function validateStockPointBeforeSave($profile, $product, $productQty)
    {
        $arrProduct = [
            $product->getId() => [
                'product' => $product,
                'qty' => $productQty
            ]
        ];

        $isAllow = $this->validateStockPointProduct->checkProductAllowStockPoint(
            $profile,
            $product,
            $arrProduct,
            true
        );
        return $isAllow;
    }
}
