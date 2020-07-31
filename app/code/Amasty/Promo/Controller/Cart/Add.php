<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Promo
 */


namespace Amasty\Promo\Controller\Cart;

class Add extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Amasty\Promo\Model\Registry
     */
    protected $promoRegistry;

    /**
     * @var \Amasty\Promo\Helper\Cart
     */
    protected $promoCartHelper;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Amasty\Promo\Model\Registry $promoRegistry,
        \Amasty\Promo\Helper\Cart $promoCartHelper
    ) {
        parent::__construct($context);

        $this->promoRegistry = $promoRegistry;
        $this->promoCartHelper = $promoCartHelper;
    }

    public function execute()
    {
        $productId = (int)$this->getRequest()->getParam('product_id');

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $this->_objectManager->create(
            'Magento\Catalog\Model\Product'
        );

        $product->load($productId);

        if ($product->getId()) {
            $limits = $this->promoRegistry->getLimits();

            $sku = $product->getSku();

            $addAllRule = isset($limits[$sku]) && $limits[$sku] > 0;
            $addOneRule = false;
            if (!$addAllRule) {
                foreach ($limits['_groups'] as $ruleId => $rule) {
                    if (in_array($sku, $rule['sku'])) {
                        $addOneRule = $ruleId;
                    }
                }
            }

            if ($addAllRule || $addOneRule) {
                $params = $this->getRequest()->getParams();
                $requestOptions = array_intersect_key($params, array_flip([
                    'super_attribute', 'options', 'super_attribute'
                ]));

                $this->promoCartHelper->addProduct(
                    $product, 1, $addOneRule, $requestOptions
                );
            }
        }

        $this->promoCartHelper->updateQuoteTotalQty(true);

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setRefererOrBaseUrl();

        return $resultRedirect;
    }
}
