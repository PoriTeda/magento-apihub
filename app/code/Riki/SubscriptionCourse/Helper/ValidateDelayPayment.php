<?php

namespace Riki\SubscriptionCourse\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;

class ValidateDelayPayment extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subscriptionPageHelper;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * ValidateDelayPayment constructor.
     * @param Context $context
     * @param \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper
     * @param StoreManagerInterface $storeManagerInterface
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        Context $context,
        \Riki\SubscriptionPage\Helper\Data $subscriptionPageHelper,
        StoreManagerInterface $storeManagerInterface,
        ProductRepositoryInterface $productRepository
    ) {
        $this->storeManagerInterface = $storeManagerInterface;
        $this->productRepository = $productRepository;
        $this->subscriptionPageHelper = $subscriptionPageHelper;
        parent::__construct($context);
    }

    /**
     * Check maximum order qty when course is delay payment
     *
     * @param $prepareData
     * @param $courseId
     *
     * @return array
     */
    public function checkMaximumOrderQty($prepareData, $courseId)
    {
        $arrResult = ['has_error' => 0];
        $productErrors = [];
        $courseModel = $this->subscriptionPageHelper->getSubscriptionCourseModelFromCourseId($courseId);

        if ($courseModel->getData('maximum_order_qty') > 0 &&
            !empty($prepareData)
        ) {
            $arrResult['maximum_order_qty'] = $courseModel->getData('maximum_order_qty');
            foreach ($prepareData as $productId => $productInfo) {
                if ($productInfo['qty'] > $arrResult['maximum_order_qty']) {
                    $arrResult['has_error'] = 1;
                    if (isset($productInfo['product']) &&
                        $productInfo['product'] instanceof \Magento\Catalog\Model\Product
                    ) {
                        $productErrors[] = $productInfo['product']->getName();
                    } else {
                        $productModel = $this->_initProductWithId($productId);
                        if (!$productModel) {
                            $productErrors[] = $productModel->getName();
                        }
                    }
                }
            }
            if ($arrResult['has_error']) {
                $arrResult['product_errors'] = implode(', ', $productErrors);
                return $arrResult;
            }
        }

        return $arrResult;
    }

    /**
     * Validate maximum order qty of delay payment by quote
     *
     * @param $quote
     * @param $courseId
     * @return array
     */
    public function validateMaximumQtyDelayPaymentByQuote($quote, $courseId)
    {
        $prepareData = [];

        foreach ($quote->getAllVisibleItems() as $item) {
            if ($item->getData('is_riki_machine') ==1) {
                continue;
            }
            $buyRequest = $item->getBuyRequest();
            if (isset($buyRequest['options']['ampromo_rule_id'])) {
                continue;
            }

            if (strtoupper($item->getUnitCase()) ==
                \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                $unitQty = ((int)$item->getUnitQty() != 0) ? (int)$item->getUnitQty(): 1;
                $qty = $item->getQty()/$unitQty;
            } else {
                $qty = $item->getQty();
            }

            /** prepare data */
            $prepareData[$item->getProduct()->getId()] = [
                'qty' => $qty,
                'product' => $item->getProduct()
            ];
        }

        return $this->checkMaximumOrderQty($prepareData, $courseId);
    }

    /**
     * Return message error validate maximum order qty delay payment
     *
     * @param $productErrors
     * @param $maximumOrderQty
     * @return \Magento\Framework\Phrase
     */
    public function getMessageErrorDelayPayment($productErrors, $maximumOrderQty)
    {
        $message = __(
            'The product %product have been selected over %quantity quantity for purchasing.', [
                'product' => $productErrors,
                'quantity' => $maximumOrderQty
            ]
        );

        return $message;
    }

    /**
     * Prepare product data
     *
     * @param $dataProducts
     * @return array
     */
    public function prepareProductData($dataProducts, $checkCase = false)
    {
        $arrResult = [];

        foreach ($dataProducts as $productId => $info) {
            if (isset($info['case_display']) &&
                strtoupper($info['case_display']) ==
                \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE &&
                $checkCase
            ) {
                $arrResult[$productId]['qty'] = ($info['qty'] / $info['unit_qty']);
                $arrResult[$productId]['product'] = $this->_initProductWithId($productId);
                $arrResult[$productId]['case_display'] = $info['case_display'];
                $arrResult[$productId]['unit_qty'] = $info['unit_qty'];
            } else {
                $arrResult[$productId]['qty'] = $info['qty'];
                $arrResult[$productId]['product'] = $this->_initProductWithId($productId);
                $arrResult[$productId]['case_display'] = $info['case_display'];
                $arrResult[$productId]['unit_qty'] = 1;
            }
        }
        return $arrResult;
    }

    /**
     * Initialize product instance from request data
     * @param $id
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    protected function _initProductWithId($id)
    {
        $productId = (int)$id;
        if ($productId) {
            $storeId = $this->storeManagerInterface->getStore()->getId();
            try {
                return $this->productRepository->getById($productId, false, $storeId);
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                return false;
            }
        }
        return false;
    }
}
