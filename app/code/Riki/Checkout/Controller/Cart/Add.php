<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Checkout\Controller\Cart;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Add extends \Magento\Checkout\Controller\Cart\Add
{
    /**
     * @var \Magento\Checkout\Helper\Cart
     */
    protected $checkoutCartHelper;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    protected $jsonHelper;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $localResolver;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Riki\SubscriptionPage\Helper\CheckRequestLineApp
     */
    protected $helperLineApp;

    /**
     * @var \Riki\DeliveryType\Model\Config\DeliveryDateSelection
     */
    protected $deliveryDateSelectionConfig;

    /**
     * @var \Magento\GiftWrapping\Helper\Data
     */
    protected $helperData;

    /**
     * @var \Magento\GiftWrapping\Api\WrappingRepositoryInterface
     */
    protected $wrappingRepository;

    /**
     * Add constructor.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Checkout\Helper\Cart $checkoutCartHelper
     * @param \Magento\Checkout\Model\Cart $cart
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\SubscriptionPage\Helper\CheckRequestLineApp $helperLineApp
     * @param \Riki\DeliveryType\Model\Config\DeliveryDateSelection $deliveryDateSelectionConfig
     * @param \Magento\GiftWrapping\Helper\Data $helperData
     * @param \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Checkout\Helper\Cart $checkoutCartHelper,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        \Riki\SubscriptionPage\Helper\CheckRequestLineApp $helperLineApp,
        \Riki\DeliveryType\Model\Config\DeliveryDateSelection $deliveryDateSelectionConfig,
        \Magento\GiftWrapping\Helper\Data $helperData,
        \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $productRepository
        );
        $this->checkoutCartHelper = $checkoutCartHelper;
        $this->escaper = $escaper;
        $this->jsonHelper = $jsonHelper;
        $this->localResolver = $localeResolver;
        $this->logger = $logger;
        $this->helperLineApp = $helperLineApp;
        $this->deliveryDateSelectionConfig = $deliveryDateSelectionConfig;
        $this->helperData = $helperData;
        $this->wrappingRepository = $wrappingRepository;
    }

    /**
     * Add product to shopping cart action
     *      overwrite core logic to remove success message at checkout page
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $params = $this->getRequest()->getParams();

        try {
            if (isset($params['qty'])) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->localResolver->getLocale()]
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            if (!$product) {
                return $this->goBack();
            }

            $this->cart->addProduct($product, $params);
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }

            $quote = $this->cart->getQuote();

            $disableChangeDeliveryDate = $this->deliveryDateSelectionConfig->getDisableChangeDeliveryDateConfig();
            if ($disableChangeDeliveryDate) {
                $quote->setData('allow_choose_delivery_date', 0);
            }

            $this->cart->save();

            $listGiftWrapping = $this->getRequest()->getParam('gift_wrapping');

            if ($listGiftWrapping) {
                foreach ($quote->getAllVisibleItems() as $item) {
                    if ($item->getProductId() == $product->getId()) {
                        $this->updateWrappingItem($listGiftWrapping, $item);
                    }
                }
            }

            /**
             * @todo remove wishlist observer \Magento\Wishlist\Observer\AddToCart
             */
            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                return $this->goBack(null, $product);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNotice(
                    $this->escaper->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addError($this->escaper->escapeHtml($message));
                }
            }

            $url = $this->getRedirectUrl();

            return $this->goBack($url);
        } catch (\Exception $e) {
            $this->messageManager->addException($e, __('We can\'t add this item to your shopping cart right now.'));
            $this->logger->critical($e);
            return $this->goBack();
        }
    }

    /**
     * Resolve response
     *      Move to public function to used for plugin
     *
     * @param string $backUrl
     * @param \Magento\Catalog\Model\Product $product
     * @return $this|\Magento\Framework\Controller\Result\Redirect
     */
    protected function goBack($backUrl = null, $product = null)
    {
        return $this->addToCartResponse($backUrl, $product);
    }

    /**
     * @param null $backUrl
     * @param null $product
     * @return \Magento\Framework\Controller\Result\Redirect|mixed|null|string
     */
    public function addToCartResponse($backUrl = null, $product = null)
    {
        if (!$this->getRequest()->isAjax()) {
            if (empty($backUrl)) {
                $redirectUrl = $this->getBackUrl($this->_redirect->getRefererUrl());
            } else {
                $redirectUrl = $backUrl;
            }

            $backUrl = $this->helperLineApp->checkRequestAddParam($redirectUrl);

            return parent::_goBack($backUrl);
        }

        $result = [];

        if ($backUrl || $backUrl = $this->getBackUrl()) {
            $result['backUrl'] = $this->helperLineApp->checkRequestAddParam($backUrl);
        } else {
            if ($product && !$product->getIsSalable()) {
                $result['product'] = [
                    'statusText' => __('Out of stock')
                ];
            }
        }

        $this->getResponse()->representJson(
            $this->jsonHelper->jsonEncode($result)
        );
    }

    /**
     * Get redirect Url
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        $url = $this->_checkoutSession->getRedirectUrl(true);

        if (!$url) {
            $cartUrl = $this->checkoutCartHelper->getCartUrl();
            $url = $this->_redirect->getRedirectUrl($cartUrl);
        }

        return $url;
    }

    private function updateWrappingItem($wrappingId, $item)
    {
        if ($this->helperData->isGiftWrappingAvailableForItems()) {
            try {
                if ($wrappingId == -1) {
                    $gwId = '';
                    $gCode = '';
                    $sapCode = '';
                    $gw = '';
                } else {
                    $wrapping = $this->wrappingRepository->get($wrappingId);
                    if (!$wrapping) {
                        return false;
                    }
                    $gwId = $wrapping->getId();
                    $gCode = $wrapping->getGiftCode();
                    $sapCode = $wrapping->getSapCode();
                    $gw = $wrapping->getGiftName();
                }
                $item->setGwId($gwId)
                    ->setGiftCode($gCode)
                    ->setSapCode($sapCode)
                    ->setGiftWrapping($gw);

                if (empty($gwId)) {
                    $item->setGwPrice(0)
                        ->setGwBasePrice(0)
                        ->setGwBaseTaxAmount(0)
                        ->setGwTaxAmount(0);
                }

                $item->save();
            } catch (\Exception $e) {
                $this->logger->critical($e);

                return false;
            }
        }
    }
}
