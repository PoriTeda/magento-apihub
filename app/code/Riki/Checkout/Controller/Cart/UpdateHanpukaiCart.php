<?php

namespace Riki\Checkout\Controller\Cart;

use Magento\Checkout\Model\Cart as CustomerCart;
use Riki\Subscription\Model\Constant;
use Riki\AdvancedInventory\Model\Stock as AdvancedInventoryStock;

class UpdateHanpukaiCart extends \Magento\Checkout\Controller\Cart
{
    /**
     * @var \Riki\Checkout\Helper\Data
     */
    protected $rikiCheckoutHelperData;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $resolverInterface;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $loggerInterface;

    /**
     * @var \Riki\Catalog\Helper\Data
     */
    protected $catalogHelper;

    protected $productErrors = null;

    protected $maximumOrderQty = null;

    /**
     * @var \Riki\Catalog\Model\StockState
     */
    protected $stockState;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * Update Hanpukai Cart constructor.
     * @param \Riki\Catalog\Helper\Data $catalogHelper
     * @param \Psr\Log\LoggerInterface $loggerInterface
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\Locale\ResolverInterface $resolverInterface
     * @param \Riki\Checkout\Helper\Data $rikiCheckoutHelperData
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Riki\Catalog\Helper\Data $catalogHelper,
        \Psr\Log\LoggerInterface $loggerInterface,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Locale\ResolverInterface $resolverInterface,
        \Riki\Checkout\Helper\Data $rikiCheckoutHelperData,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        \Riki\Catalog\Model\StockState $stockState,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
    ) {
        $this->catalogHelper = $catalogHelper;
        $this->loggerInterface = $loggerInterface;
        $this->escaper = $escaper;
        $this->resolverInterface = $resolverInterface;
        $this->rikiCheckoutHelperData = $rikiCheckoutHelperData;
        $this->stockState = $stockState;
        $this->subscriptionValidator = $subscriptionValidator;
        parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager, $formKeyValidator, $cart);
    }

    /**
     * Empty customer's shopping cart
     *
     * @return void
     */
    protected function _emptyShoppingCart()
    {
        try {
            $this->cart->truncate()->save();
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            $this->messageManager->addError($exception->getMessage());
        } catch (\Exception $exception) {
            $this->messageManager->addException($exception, __('We can\'t update the shopping cart.'));
        }
    }

    /**
     * Update qty for cart
     *
     * @return null
     */
    protected function _updateShoppingCart()
    {
        $quote = $this->_checkoutSession->getQuote();
        $courseId = $quote->getData('riki_course_id');
        $hanpukaiChangeAllQty = $this->getRequest()->getParam('cart-hanpukai-change-all-qty');
        $configHanpukaiProduct = $this->rikiCheckoutHelperData->getArrProductFirstDeliveryHanpukai($courseId);

        $errorCode = $this->isNotValid($quote, $hanpukaiChangeAllQty, $configHanpukaiProduct);
        switch ($errorCode) {
            case 1:
                $message = $this->subscriptionValidator->getMessageMaximumError(
                    $this->productErrors,
                    $this->maximumOrderQty
                );
                $this->messageManager->addError($message);
                return;
            default:
                break;  /** Do nothing */

        }

        $quoteItemData = $this->prepareDataForValidateStock($quote, $hanpukaiChangeAllQty);

        /*validate stock before update quote item*/
        foreach ($quoteItemData as $productId => $productInfo) {
            $canAssigned = $this->stockState->canAssigned(
                $productInfo['product'],
                $productInfo['qty'],
                $this->stockState->getPlaceIds()
            );

            /*stock is not enough*/
            if (!$canAssigned) {
                $messageError = sprintf(
                    __("We don't have as many \"%s\" as you requested."),
                    $productInfo['product_name']
                );

                $this->messageManager->addError($messageError);
                return;
            }
        }

        $totalQtyInFo = $this->calculateTotalQtyFo($hanpukaiChangeAllQty);
        $maximumOrderQtyConfig = $this->getConfig(AdvancedInventoryStock::ADVANCED_INVENTORY_MAXIMUM_CART_STOCK);
        if ($maximumOrderQtyConfig > 0 && $maximumOrderQtyConfig < $totalQtyInFo) {
            $messageError = sprintf(__('Please limit the total number of items you order at one time to %s pieces or less.'), $maximumOrderQtyConfig);
            $this->messageManager->addError($messageError);
            return;
        }

        try {
            $cartData = $this->getRequest()->getParam('cart');
            if (is_array($cartData)) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->resolverInterface->getLocale()]
                );
                $previousFactor = $this->rikiCheckoutHelperData->calculateFactor($configHanpukaiProduct, $cartData, $quote);
                $arrMapItemIdToProductId = $this->rikiCheckoutHelperData->mapQuoteItemIdToProductId($quote);
                if ($previousFactor === false) {
                    foreach ($cartData as $index => $data) {
                        if (isset($data['qty'])) {
                            // Reset product qty of hanpukai
                            $productId = $arrMapItemIdToProductId[$index];
                            if (in_array($productId, array_keys($configHanpukaiProduct))) {
                                $cartData[$index]['qty'] = $configHanpukaiProduct[$productId]['qty'];
                            } else {
                                // Additional product may be gift product => not reset
                                $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                            }
                        }
                    }
                    $this->_checkoutSession->getQuote()->setData(Constant::RIKI_HANPUKAI_QTY, 1);
                } else {
                    foreach ($cartData as $index => $data) {
                        if (isset($data['qty'])) {
                            // Reset product qty of hanpukai.
                            $productId = $arrMapItemIdToProductId[$index];
                            if (in_array($productId, array_keys($configHanpukaiProduct))) {
                                // just update qty for product allow hanpukai not gift product
                                $cartData[$index]['qty'] = ($data['qty'] / $previousFactor) * $hanpukaiChangeAllQty;
                            } else {
                                // Additional product may be gift product => not reset
                                $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                            }
                        }
                    }
                    $this->_checkoutSession->getQuote()->setData(Constant::RIKI_HANPUKAI_QTY, $hanpukaiChangeAllQty);
                }

                if (!$this->cart->getCustomerSession()->getCustomerId() && $this->cart->getQuote()->getCustomerId()) {
                    $this->cart->getQuote()->setCustomerId(null);
                }
                $cartData = $this->cart->suggestItemsQty($cartData);
                $this->cart->updateItems($cartData)->save();
            }
            // Update cart done set again change qty
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError(
                $this->escaper->escapeHtml($e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t update the shopping cart.'));
            $this->loggerInterface->critical($e);
        }
    }

    /**
     * Execute submit form case hanpukai
     *
     * @return null
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $updateAction = (string)$this->getRequest()->getParam('update_hanpukai_cart_action');

        switch ($updateAction) {
            case 'empty_cart':
                $this->_emptyShoppingCart();
                break;
            case 'update_qty':
                $this->_updateShoppingCart();
                break;
            default:
                $this->_updateShoppingCart();
        }

        return $this->_goBack();
    }

    /**
     * Prepare data for validate back order
     *
     * @param $quote
     * @param $hanpukaiChangeAllQty
     * @return array
     */
    protected function prepareDataForValidateStock($quote, $hanpukaiChangeAllQty)
    {
        $arrResult = [];
        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            return $arrResult;
        }
        /** @var $quote \Magento\Quote\Model\Quote */
        $items = $quote->getAllItems();
        if (!empty($items)) {
            foreach ($items as $item) {
                $buyRequest = $item->getBuyRequest();
                if (isset($buyRequest['options']['ampromo_rule_id'])) {
                    continue;
                }
                if ($item->getData('is_riki_machine')) {
                    continue;
                }
                $product = $item->getProduct();
                $arrResult[$item->getId()]['product_id'] = $product->getId();
                $arrResult[$item->getId()]['product'] = $product;
                $arrResult[$item->getId()]['qty'] = $item->getQty() * $hanpukaiChangeAllQty;
                $arrResult[$item->getId()]['product_name'] = $product->getName();
                $arrResult[$item->getId()]['assignation'] = '';
            }
        }
        return $arrResult;
    }

    /**
     * Calculate Total Qty Fo
     *
     * @param $quote
     * @param $hanpukaiChangeAllQty
     *
     * @return int
     */
    public function calculateTotalQtyFo($hanpukaiChangeAllQty)
    {
        $totalQty = 0;
        $quote = $this->_checkoutSession->getQuote();
        $items = $quote->getAllItems();
        if (!empty($items)) {
            foreach ($items as $item) {
                list($unitQty,$caseDisplay) = $this->catalogHelper->getProductUnitInfo($item->getProduct()->getId());
                $buyRequest = $item->getBuyRequest();
                if (isset($buyRequest['options']['ampromo_rule_id']) || $item->getData('is_riki_machine')) {
                    if (strtoupper($caseDisplay)
                        == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                        $totalQty = $totalQty + $item->getQty() / $unitQty;
                    } else {
                        $totalQty  = $totalQty + $item->getQty();
                    }
                } else {
                    if (strtoupper($caseDisplay)
                        == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                        $totalQty = $totalQty + ($item->getQty() / $unitQty) * $hanpukaiChangeAllQty;
                    } else {
                        $totalQty = $totalQty + $item->getQty() * $hanpukaiChangeAllQty;
                    }
                }
            }
        }
        return $totalQty;
    }

    /**
     * Get Store Config
     *
     * @param $path
     *
     * @return string
     */
    public function getConfig($path)
    {

        return $this->_scopeConfig->getValue(
            $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Validate items
     *
     * @param $quote
     * @param $hanpukaiQty
     * @return int
     */
    protected function isNotValid($quote, $hanpukaiQty, $configHanpukaiProduct)
    {
        $items = [];
        $courseId = $quote->getData('riki_course_id');
        $quoteItems = $quote->getAllVisibleItems();
        if (!empty($quoteItems)) {
            foreach ($quoteItems as $item) {
                if ($item->getData('is_riki_machine') ==1) {
                    continue;
                }
                $buyRequest = $item->getBuyRequest();
                if (isset($buyRequest['options']['ampromo_rule_id'])) {
                    continue;
                }

                $items[$item->getProductId()]['qty'] = $hanpukaiQty;
                $items[$item->getProductId()]['case_display'] = $item->getUnitCase();
                $items[$item->getProductId()]['unit_qty'] = $item->getUnitQty();
            }
        }
        /**
         * Process qty for hanpukai
         */
        if ($hanpukaiQty > 0 && is_array($configHanpukaiProduct)) {
            foreach ($configHanpukaiProduct as $id => $info) {
                $items[$id]['qty'] = $info['qty'] * $hanpukaiQty;
            }
        }

        /** Validate maximum qty restriction */
        $prepareData = $this->subscriptionValidator->prepareProductData($items);
        $validateMaximumQty = $this->subscriptionValidator
            ->setCourseId($courseId)
            ->setProductCarts($prepareData)
            ->validateMaximumQtyRestriction();

        if ($validateMaximumQty['error']) {
            $this->productErrors = $validateMaximumQty['product_errors'];
            $this->maximumOrderQty = $validateMaximumQty['maxQty'];
            return 1;
        }
    }
}