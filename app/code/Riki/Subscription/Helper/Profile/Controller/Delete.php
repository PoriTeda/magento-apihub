<?php

namespace Riki\Subscription\Helper\Profile\Controller;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Riki\Subscription\Model\Constant;
use Symfony\Component\Config\Definition\Exception\Exception;

class Delete
{

    protected $om;

    protected $_logger;

    protected $_profileFactory;

    /**
     * @var \Riki\Subscription\Model\ProductCart\ProductCartFactory
     */
    protected $_productCartFactory;

    protected $_profileId;

    protected $_objProfileCache;

    /**
     * @var \Riki\Subscription\Controller\Adminhtml\Profile\Delete
     */
    protected $_action;

    protected $_productCart;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $subscriptionOrderHelper;

    /**
     * Delete constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory
     * @param \Riki\Subscription\Helper\Order $subscriptionOrderHelper
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Subscription\Model\ProductCart\ProductCartFactory $productCartFactory,
        \Riki\Subscription\Helper\Order $subscriptionOrderHelper
    )
    {
        $this->om = $objectManager;
        $this->_profileFactory = $profileFactory;
        $this->_logger = $logger;
        $this->_productCartFactory = $productCartFactory;
        $this->subscriptionOrderHelper = $subscriptionOrderHelper;
    }

    /**
     * @param $action
     * @return mixed
     */
    public function execute($action)
    {
        // 1

        $this->_action = $action;
        $this->_objProfileCache = $this->_action->getProfileCache();
        $this->_profileId = (int)$this->_action->getRequest()->getParam('id');
        $cartId = $this->_action->getRequest()->getParam('pcart_id'); // Note: new item added, this item will be string
        $allItem = $this->_action->getRequest()->getParam('all_item_delete'); // Note: new item added, this item will be string
        try {

            /**
             * check profile exit
             */
            if ($this->_objProfileCache == false || empty($this->_objProfileCache->getData("profile_id"))) {
                $this->_logger->notice('Empty session or empty profile_id');
                throw new LocalizedException(__("Some thing went wrong, please reload page."));
            }

            /**
             * validate profile
             */
            /** @var \Riki\Subscription\Model\Profile\Profile $_objProfile */
            $_objProfile = $this->_profileFactory->create();
            $_objProfile->load($this->_profileId);

            $validateResult = $this->validateDeleteProductWithProfileStatus($_objProfile);
            if (is_string($validateResult)) {
                throw new LocalizedException(__($validateResult));
            }

            /**
             * Load product
             */
            $this->_productCart = $this->_objProfileCache['product_cart'];

            $oldProductCart = $this->subscriptionOrderHelper->cloneProductCartData($this->_productCart);
            /**
             * Check product delete single or multiple
             */
            if (isset($allItem) && $allItem != null) {
                if ((!isset($cartId) || $cartId == null) && $allItem == -1) {
                    $this->deleteAllItemCheck();
                } else {
                    $allItem = \Zend_Json::decode($allItem);
                    $this->deleteAllItemCheck($allItem);
                }
            } else {
                $this->deleteOneItem($cartId);
            }
            $this->_objProfileCache['product_cart'] = $this->_productCart;
            $subscriptionCourse = $this->subscriptionOrderHelper->loadCourse($_objProfile->getData('course_id'));
            $validateResult = $this->subscriptionOrderHelper->validateSimulateOrderAmountRestriction(
                $subscriptionCourse,
                $this->_objProfileCache
            );
            if (!$validateResult['status']) {
                $this->_objProfileCache['product_cart'] = $oldProductCart;
                $this->_objProfileCache[Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED] = false;
                throw new LocalizedException($validateResult['message']);
            } else {
                $this->_objProfileCache[Constant::SUBSCRIPTION_PROFILE_HAS_CHANGED] = true;
                $this->_action->saveToCache($this->_objProfileCache);
                $result['status'] = true;
                $result['message'] = __('Product temporarily deleted, please click "Confirm Changes" button to delete actualy');
                return $action->getResponse()->setBody(\Zend_Json_Encoder::encode($result));
            }
        } catch (\Exception $e) {
            return $action->getResponse()->setBody(\Zend_Json_Encoder::encode([
                'status' => false,
                'message' => $e->getMessage()
            ]));
        }
    }

    public function checkAndDeleteChildProductOfBundle($productCart, $deleteProductObject)
    {
        $arrProductDelete = array();
        $productType = $deleteProductObject->getData('product_type');
        if ($productType == 'bundle') {
            $productId = $deleteProductObject->getData('product_id');
            foreach ($productCart as $item) {
                if ($item->getData('parent_item_id') == $productId) {
                    $arrProductDelete[] = $item->getData('cart_id');
                }
            }
        }
        return $arrProductDelete;
    }

    /**
     * Do not allow to delete product if the current profile is in stage
     *
     * @param $profile
     * @return bool|\Magento\Framework\Phrase
     */
    public function validateDeleteProductWithProfileStatus(\Riki\Subscription\Model\Profile\Profile $profile)
    {

        if ($this->_isProfileInStage($profile)) {
            $this->_logger->notice(__('Profile is in stage'));

            return __('Cannot delete this product');
        }

        return true;
    }

    /**
     * @param $profileId
     * @return bool
     */
    private function _isProfileInStage($profileId)
    {

        if ($profileId instanceof \Riki\Subscription\Model\Profile\Profile) {
            $_objProfile = $profileId;
        } else {

            $_objProfile = $this->_profileFactory->create();
            $_objProfile->load((int)$profileId);
        }

        return $_objProfile->isInStage();
    }

    /**
     *  Delete one item
     * @param $pcartId
     * @throws LocalizedException
     */
    public function deleteOneItem($pcartId)
    {
        // 4
        if (!$pcartId || empty($this->_productCart[$pcartId])) {
            $this->_logger->notice(sprintf("data debug %s",
                \Zend_Json_Encoder::encode([['pcart_id' => $pcartId, 'product_cart' => $this->_productCart]], true)));
            throw new LocalizedException(__('The product do not exists. Please reload the page and try again.'));
        }

        // 5 - Delete in session
        $arrProductDelete = $this->checkAndDeleteChildProductOfBundle($this->_productCart,
            $this->_productCart[$pcartId]);
        unset($this->_productCart[$pcartId]);
        if (count($arrProductDelete) > 0) {
            foreach ($arrProductDelete as $productCartDeleteId) {
                unset($this->_productCart[$productCartDeleteId]);
            }
        }
    }

    /**
     * @param null $allItemDelete
     * @throws LocalizedException
     */
    public function deleteAllItemCheck($allItemDelete = null)
    {
        if ($allItemDelete != null) {
            if (count($allItemDelete) > 0) {
                foreach ($allItemDelete as $cartId) {
                    $this->deleteOneItem($cartId);
                }
            } else {
                $this->_logger->notice(sprintf("data debug %s",
                    \Zend_Json_Encoder::encode([['product_cart' => $this->_productCart]], true)));
                throw new LocalizedException(__('The product do not exists. Please reload the page and try again.'));
            }
        } else if (!empty($this->_productCart)) {
            foreach ($this->_productCart as $key => $value) {
                $this->deleteOneItem($key);
            }
        }
    }

}