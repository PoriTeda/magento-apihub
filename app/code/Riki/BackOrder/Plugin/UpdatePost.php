<?php

namespace Riki\BackOrder\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Riki\AdvancedInventory\Model\Stock as AdvancedInventoryStock;
use Riki\DelayPayment\Helper\Data as DelayPaymentHelper;

class UpdatePost extends \Magento\Checkout\Controller\Cart\UpdatePost
{
    /**
     * @var \Wyomind\AdvancedInventory\Model\StockRepositery
     */
    protected $stockRepository;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     * @var \Riki\Catalog\Helper\Data
     */
    protected $catalogHelper;

    /**
     * @var \Riki\SubscriptionCourse\Helper\ValidateDelayPayment
     */
    protected $helperDelayPayment;

    /**
     * @var \Riki\Catalog\Model\StockState
     */
    protected $stockState;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $resolver;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * UpdatePost constructor.
     * @param \Riki\Catalog\Helper\Data $catalogHelper
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Wyomind\AdvancedInventory\Model\StockRepositery $stockRepository
     * @param \Riki\SubscriptionCourse\Helper\ValidateDelayPayment $helperDelayPayment
     * @param \Riki\Catalog\Model\StockState $stockState
     * @param \Magento\Framework\Locale\ResolverInterface $resolver
     * @param \Magento\Framework\Escaper $escaper
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Riki\Catalog\Helper\Data $catalogHelper,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        \Wyomind\AdvancedInventory\Model\StockRepositery $stockRepository,
        \Riki\SubscriptionCourse\Helper\ValidateDelayPayment $helperDelayPayment,
        \Riki\Catalog\Model\StockState $stockState,
        \Magento\Framework\Locale\ResolverInterface $resolver,
        \Magento\Framework\Escaper $escaper,
        \Psr\Log\LoggerInterface $logger,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
    ) {
        parent::__construct($context, $scopeConfig, $checkoutSession, $storeManager, $formKeyValidator, $cart);
        $this->stockRepository = $stockRepository;
        $this->catalogHelper = $catalogHelper;
        $this->cart = $cart;
        $this->helperDelayPayment = $helperDelayPayment;
        $this->stockState = $stockState;
        $this->resolver = $resolver;
        $this->escaper = $escaper;
        $this->logger = $logger;
        $this->subscriptionValidator = $subscriptionValidator;
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
     * Update shopping cart data action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function aroundExecute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }
        $updateAction = (string)$this->getRequest()->getParam('update_cart_action');
        $isValid = true;
        $quote = $this->cart->getQuote();
        $arrProduct  = $this->getAllProductInQuote($quote);
        $cartData = $this->getRequest()->getParam('cart');

        if($cartData) {
            foreach ($cartData as $itemId => $data) {
                if (array_key_exists($itemId, $arrProduct)) {
                    $arrProductInfo = $arrProduct[$itemId];

                    /*validate stock before update quote item*/
                    $canAssigned = $this->stockState->canAssigned(
                        $arrProductInfo['product'],
                        $data['qty'],
                        $this->stockState->getPlaceIds()
                    );

                    if (!$canAssigned) {
                        $message = sprintf(
                            __("We don't have as many \"%s\" as you requested."),
                            $arrProductInfo['product_name']
                        );
                        $this->messageManager->addErrorMessage($message);
                        $isValid = false;
                        break;
                    }
                }
            }

            $totalQtyInFo = $this->calculateTotalQty($quote, $cartData);
            $maximumOrderQtyConfig = $this->getConfig(AdvancedInventoryStock::ADVANCED_INVENTORY_MAXIMUM_CART_STOCK);
            if ($maximumOrderQtyConfig > 0 && $maximumOrderQtyConfig < $totalQtyInFo) {
                $message = sprintf(__('I am sorry. <br> In the Nestle Online shopping online shop, we have restricted the maximum number of items as %s at one order. Sorry to trouble you, but please change the number of items in the cart to %s pieces or less.'),
                    $maximumOrderQtyConfig, $maximumOrderQtyConfig);

                $this->messageManager->addError($message);
                $isValid = false;
            }

            /** Validate maximum qty restriction */
            if ($quote->getData('riki_course_id')) {
                $prepareData = [];
                foreach ($cartData as $itemId => $data) {
                    if (array_key_exists($itemId, $arrProduct)) {
                        // Only validate maximum qty for product has changed qty
                        if ($data['is_changed_qty']) {
                            $productId = $arrProduct[$itemId]['product_id'];
                            $arrProduct[$itemId]['qty'] = $data['qty'];

                            if (!isset($prepareData[$productId])) {
                                $prepareData[$productId] = $arrProduct[$itemId];
                            }
                        }
                    }
                }

                $prepareData = $this->subscriptionValidator->prepareProductData($prepareData);
                $validateMaximumQty = $this->subscriptionValidator
                    ->setCourseId($quote->getData('riki_course_id'))
                    ->setProductCarts($prepareData)
                    ->validateMaximumQtyRestriction();
                if ($validateMaximumQty['error']) {
                    $message = $this->subscriptionValidator->getMessageMaximumError(
                        $validateMaximumQty['product_errors'],
                        $validateMaximumQty['maxQty']
                    );

                    $this->messageManager->addError($message);
                    $isValid = false;
                }
            }
        }

        switch ($updateAction) {
            case 'empty_cart':
                $this->_emptyShoppingCart();
                break;
            case 'update_qty':
                if (!$isValid) {
                    return $this->_goBack();
                }
                $this->_updateShoppingCart();
                break;
            default:
                if (!$isValid) {
                    return $this->_goBack();
                }
                $this->_updateShoppingCart();
        }

        return $this->_goBack();
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
     * Calculate Total Qty
     *
     * @param $quote
     * @param $cartData
     *
     * @return int;
     */
    public function calculateTotalQty($quote, $cartData)
    {
        /* @var \Magento\Quote\Model\Quote $quote */
        $totalQty = 0;
        $items = $quote->getAllItems();
        if (!empty($items)) {
            foreach ($items as $item) {
                list($unitQty,$caseDisplay) = $this->catalogHelper->getProductUnitInfo($item->getProduct()->getId());
                if (in_array($item->getId(), array_keys($cartData)) && isset($cartData[$item->getId()]['qty'])) {
                    if (strtoupper($caseDisplay)
                        == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                        $totalQty = $totalQty + $cartData[$item->getId()]['qty'] / $unitQty;
                    } else {
                        $totalQty  = $totalQty + $cartData[$item->getId()]['qty'];
                    }
                } else {
                    if (strtoupper($caseDisplay)
                        == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                        $totalQty = $totalQty + $item->getQty() / $unitQty;
                    } else {
                        $totalQty  = $totalQty + $item->getQty();
                    }
                }
            }
        }
        return $totalQty;
    }



    /**
     * @param $quote
     *
     * @return array
     */
    public function getAllProductInQuote($quote)
    {
        /* @var \Magento\Quote\Model\Quote $quote */
        $arrResult = array();
        if (!$quote->hasItems() || $quote->getHasError() || !$quote->validateMinimumAmount()) {
            return $arrResult;
        }
        /* @var $quote \Magento\Quote\Model\Quote */
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
                $arrResult[$item->getId()]['qty'] = $item->getQty();
                $arrResult[$item->getId()]['product_name'] = $product->getName();
                $arrResult[$item->getId()]['assignation'] = '';
                $arrResult[$item->getId()]['case_display'] = $item->getUnitCase();
                $arrResult[$item->getId()]['unit_qty'] = $item->getUnitQty();
            }
        }
        return $arrResult;
    }

    /**
     * Update customer's shopping cart
     *
     * @return void
     */
    protected function _updateShoppingCart()
    {
        try {
            $cartData = $this->getRequest()->getParam('cart');
            if (is_array($cartData)) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->resolver->getLocale()]
                );
                foreach ($cartData as $index => $data) {
                    if (isset($data['qty'])) {
                        $cartData[$index]['qty'] = $filter->filter(trim($data['qty']));
                    }
                }
                if (!$this->cart->getCustomerSession()->getCustomerId() && $this->cart->getQuote()->getCustomerId()) {
                    $this->cart->getQuote()->setCustomerId(null);
                }

                $cartData = $this->cart->suggestItemsQty($cartData);
                $this->cart->updateItems($cartData);

                // Validate order total amount threshold
                $this->cart->setValidateMaxMinCourse(true);
                $this->cart->save();
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->messageManager->addError(
                $this->escaper->escapeHtml($e->getMessage())
            );
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t update the shopping cart.'));
            $this->logger->critical($e);
        }
    }
}