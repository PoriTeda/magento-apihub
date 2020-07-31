<?php

namespace Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Profile;

use Magento\Framework\Exception\LocalizedException;
use Riki\Subscription\Model\Constant;
use Magento\Framework\DataObject;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;

class AddPenalty extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $_jsonHelper;

    protected $_profileFactory;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $_profileData;

    protected $_productFactory;

    protected $_productOptionFactory;

    protected $profileCacheRepository;

    /**
     * AddPenalty constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Subscription\Helper\Profile\Data $subProfileHelper
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Catalog\Model\Product\Option $productOption
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Subscription\Helper\Profile\Data $subProfileHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\Product\Option $productOption,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
    ) {
        $this->_profileFactory = $profileFactory;
        $this->_registry = $registry;
        $this->_profileData = $subProfileHelper;
        $this->_productFactory = $productFactory;
        $this->_productOptionFactory = $productOption;
        $this->_jsonHelper = $jsonHelper;
        $this->profileCacheRepository = $profileCacheRepository;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);

        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->_initProfile();
        $profileId = (int)$this->getRequest()->getParam('id', 0);
        if ($this->_profileData->isTmpProfileId($profileId)) {
            $profileId = $this->_profileData->getMainFromTmpProfile($profileId);
        }
        $result->setUrl($this->getUrl('profile/profile/edit', ['id' => $profileId]));

        if ($profile) {
            if ($profile->isWaitingToDisengaged()) {
                $productIds = $this->getRequest()->getParam('product');

                foreach ($productIds as $productId) {
                    $this->addPenaltyProductToProfile($profile, $productId);
                }
            } else {
                $this->messageManager->addError(__('The request is invalid.'));
            }
        } else {
            $this->messageManager->addError(__('The subscription profile does not exit.'));
        }

        return $result;
    }

    /**
     * @return bool
     */
    public function getCurrentProfileDataSession()
    {
        $profileId = $this->_request->getParam('id');
       return $this->profileCacheRepository->getProfileDataCache($profileId);
    }

    /**
     * @param \Riki\Subscription\Model\Profile\Profile $profile
     * @param $productId
     * @return $this
     */
    protected function addPenaltyProductToProfile(\Riki\Subscription\Model\Profile\Profile $profile, $productId)
    {
        $profileId = $profile->getId();

        $versionProfileId = $profileId;

        if ($this->_profileData->getTmpProfile($profileId) !== false) {
            $versionProfileId = $this->_profileData->getTmpProfile($profileId)->getData('linked_profile_id');
        }
        try {
            // 2 - Validate
            $objProfileSession = $this->getCurrentProfileDataSession();
            $arrProductCartSession = $objProfileSession['product_cart'];
            if (empty($objProfileSession) || empty($objProfileSession->getData('profile_id'))) {
                throw new LocalizedException(__('Something went wrong, please reload page.'));
            }


            // 3 Params for 3
            $data = [];
            $data['profile_id'] = $versionProfileId;
            $data['product_id'] = $productId;
            $data['qty'] = 1;
            if ($productId) {
                $billingAddress = $this->getSessionAddressId($objProfileSession, $profileId);

                if (empty($billingAddress)) {
                    $billingAddress = $profile->getCustomer()->getDefaultBilling();
                }

                $productAddress = $this->getSessionAddressId($objProfileSession, $profileId, 'shipping_address_id');

                if (empty($productAddress)) {
                    $productAddress = $profile->getCustomer()->getDefaultShipping();
                }

                // 3.1 - Group
                $productExistInProfile = false;
                foreach ($arrProductCartSession as $key => $item) {
                    if ($item->getData('profile_id') == $versionProfileId
                        && $item->getData('product_id') == $productId
                    ) {
                        $productExistInProfile = true;
                        /*
                        * check product allow spot order.
                        * if allow_spot_order set no.this product will appear
                        * as out of stock and can't  be added to subscription profile.
                        */
                        $productItemSesstion = $this->_productFactory->create()->load($productId);
                        if ($productItemSesstion->getId()) {
                            if (!$productItemSesstion->getIsSalable()) {
                                $this->messageManager->addError(__('Cannot add product to this profile.'));
                            }
                        }
                        if ($productItemSesstion->getCustomAttribute('case_display')) {
                            if ($productItemSesstion->getCustomAttribute('case_display')->getValue()
                                == CaseDisplay::CD_CASE_ONLY) {
                                if ($productItemSesstion->getCustomAttribute('unit_qty')) {
                                    $item->setData(
                                        'qty',
                                        $item['qty'] + $productItemSesstion->getCustomAttribute('unit_qty')->getValue()
                                    );
                                } else {
                                    throw new LocalizedException(__('You can not add this product.'));
                                }
                            }
                        } else {
                            $item->setData('qty', $item['qty'] + 1);
                        }
                        $arrProductCartSession[$key] = $item;
                        $objProfileSession['product_cart'] = $arrProductCartSession;
                        $this->profileCacheRepository->save($objProfileSession);
                        return $this;
                    }
                }
                // 3.2 - Collect
                $productCart = new DataObject();
                $data['shipping_address_id'] = $productAddress;
                $data['billing_address_id'] = $billingAddress;
                $productModel = $this->_productFactory->create()->load($productId);
                if ($productModel->getId()) {
                    $data['product_type'] = $productModel->getTypeId();
                    $customOptions = $this->_productOptionFactory->getProductOptionCollection($productModel);
                    $data['product_options'] = $this->_jsonHelper->jsonEncode($customOptions->getData());
                    $data['parent_item_id'] = $productModel->getParentItemId() != ''
                        ? $productModel->getParentItemId()
                        : '';
                    if (!$productExistInProfile) {
                        if ($productModel->getCustomAttribute('case_display')) {
                            if ($productModel->getCustomAttribute('case_display')->getValue() ==
                                CaseDisplay::CD_CASE_ONLY) {
                                if ($productModel->getCustomAttribute('unit_qty')) {
                                    $data['unit_qty'] = $productModel->getCustomAttribute('unit_qty')->getValue();
                                    $data['qty'] = $productModel->getCustomAttribute('unit_qty')->getValue();
                                    $data['unit_case'] = CaseDisplay::PROFILE_UNIT_CASE;
                                } else {
                                    throw new LocalizedException(__('You can not add this product.'));
                                }
                            }
                        } else {
                            $data['unit_case'] = CaseDisplay::PROFILE_UNIT_PIECE;
                            $data['qty'] = 1;
                            $data['unit_qty'] = 1;
                        }
                    }
                }

                // 3.3 - Save
                try {
                    $data['cart_id'] = 'new_' . $data['product_id'] . '_' . $data['profile_id'] . '_' . $productAddress;
                    $productCart->setData($data);
                    $arrProductCartSession[$data['cart_id']] = $productCart;
                    $objProfileSession['product_cart'] = $arrProductCartSession;

                   // $objProfileSession->setData(Constant::SESSION_PROFILE_EDIT, $objProfileSession);
                    $this->profileCacheRepository->save($objProfileSession);

                }catch (\Exception $e){

                    $this->messageManager->addError(__('Cannot add product to this profile.'));
                }
            } else {
                $this->messageManager->addError(__('Cannot add product to this profile.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $this;
    }

    /**
     * @param array $actionSession
     * @param int $profileId
     * @param string $type
     * @return string
     */
    public function getSessionAddressId($actionSession, $profileId, $type = 'billing_address_id')
    {
        $arrProductCartSession = $actionSession['product_cart'];
        if (count($arrProductCartSession) > 0) {
            foreach ($arrProductCartSession as $key => $item) {
                if ($item->getData('billing_address_id')) {
                    return $item->getData('billing_address_id');
                }
            }
        } else {
            if ($this->_profileData->getTmpProfile($profileId) !== false) {
                $profileId = $this->_profileData->getTmpProfile($profileId)->getData('linked_profile_id');
            }
            $productCartData = $this->_profileData->getArrProductCart($profileId);
            foreach ($productCartData as $key => $arrProductInfo) {
                if ($arrProductInfo['profile']->getData('billing_address_id') != '') {
                    return $arrProductInfo['profile']->getData('billing_address_id');
                }
            }
        }
        return '';
    }

    /**
     * @return mixed
     */
    protected function _initProfile()
    {
        $id = $this->getRequest()->getParam('id', 0);

        if ($id) {
            $profile = $this->_profileFactory->create()->load($id);

            if ($profile->getId()) {
                $this->_registry->register('subscription_profile_obj', $profile);
                return $profile;
            }
        }

        return null;
    }

    /**
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionProfileDisengagement::profile_disengage');
    }
}
