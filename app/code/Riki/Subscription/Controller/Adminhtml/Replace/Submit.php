<?php

namespace Riki\Subscription\Controller\Adminhtml\Replace;

use Magento\Catalog\Model\Product;
use Magento\Framework\Controller\ResultFactory;

class Submit extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;
    /**
     * @var \Magento\Framework\Locale\FormatInterface
     */
    protected $localeFormat;
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    protected $priceCurrency;
    /**
     * @var \Riki\Catalog\Model\ResourceModel\ProductStatus
     */
    protected $productStatus;
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;
    /**
     * @var \Riki\Subscription\Helper\DiscontinuedHelper
     */
    protected $discontinuedHelper;
    /**
     * @var \Riki\Subscription\Logger\LoggerReplaceProduct
     */
    protected $logger;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;

    /**
     * Submit constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Riki\Catalog\Model\ResourceModel\ProductStatus $productStatus
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Riki\Subscription\Helper\DiscontinuedHelper $discontinuedHelper
     * @param \Riki\Subscription\Logger\LoggerReplaceProduct $logger
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Riki\Catalog\Model\ResourceModel\ProductStatus $productStatus,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Helper\DiscontinuedHelper $discontinuedHelper,
        \Riki\Subscription\Logger\LoggerReplaceProduct $logger,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
    ) {
        parent::__construct($context);
        $this->productFactory = $productFactory;
        $this->localeFormat = $localeFormat;
        $this->priceCurrency = $priceCurrency;
        $this->productStatus = $productStatus;
        $this->courseFactory = $courseFactory;
        $this->discontinuedHelper = $discontinuedHelper;
        $this->logger = $logger;
        $this->profileFactory = $profileFactory;
    }
    /**
     * submit action from replace/submit product
     * replace $dis ($oldProduct) with $rep ($newProduct)
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        /**
         * Validation
         */
        if ($this->getRequest()->isPost() &&
            !empty($dis = $this->getRequest()->getParam('replace_discontinue_product', false)) &&
            !empty($rep = $this->getRequest()->getParam('replace_replacement_product', false))) {
            $isValid = true;

            $oldProduct = $this->_loadByIdOrSku($dis);

            if ($oldProduct === false) {
                $this->addError($dis);
                $isValid = false;
            } else {
                $canReplace = $this->canDiscontinuedProduct($oldProduct->getId());
                if (!$canReplace) {
                    $isValid = false;
                    $this->messageManager->addError(__("Selected product is not assigned to any Subscription Course"));
                }
            }

            $newProduct = $this->_loadByIdOrSku($rep);
            if ($newProduct === false) {
                $this->addError($rep);
                $isValid = false;
            }

            if ($isValid) {
                // advanced validation
                $error = $this->_validate($oldProduct, $newProduct);
                if ($error) {
                    $this->messageManager->addError($error);
                } else {
                    $oldId = $oldProduct->getId();
                    $newId = $newProduct->getId();

                    $emails = [];

                    $courseModel = $this->courseFactory->create();
                    $profileModel = $this->profileFactory->create();

                    // we check and send mail before the database has been updated
                    $isSendNotificationEmail = $this->getRequest()->getParam('replace_send_email', false);
                    if ($isSendNotificationEmail) {
                        $emails = $profileModel->sendNotificationEmailReplaceProduct(
                            $oldId,
                            $oldProduct->getName(),
                            $newProduct->getName()
                        );
                    }

                    $this->logger->info('Products id "'. $oldId . '" replaced by "'.$newId.'".');

                    /**
                     * Replace product in all subscription courses
                     */
                    $courseModel->replaceProduct($oldId, $newId);
                    $this->productStatus->replaceProductInCategory($oldId, $newId);

                    /**
                     * Replace product in all subscription profiles
                     */
                    $profileModel->replaceProduct($oldId, $newId);

                    if ($isSendNotificationEmail && !empty($emails)) {
                        $this->logger->info('send mail successfully for: '. implode(', ', $emails));
                    }

                    $this->messageManager->addSuccess(__('Replaced product successfully'));
                }
            }
        } else {
            $this->messageManager->addError(__('Invalid input parameters'));
        }

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $resultRedirect->setPath('*/*/index');

        return $resultRedirect;
    }

    /**
     * load product entity based ID or SKU
     *
     * @param $param string ID or SKU
     * @return Product
     */
    protected function _loadByIdOrSku($param)
    {
        $productModel = $this->productFactory->create();
        $product = $productModel->load($param);
        if (!$product->getId()) {
            $id = $productModel->getIdBySku($param);
            $product = $productModel->load($id);
            if (!$product->getId()) {
                return false;
            }
        }
        return $product;
    }

    /**
     * Validate products before replace
     *
     * @param $discontinuedProduct Product
     * @param $replacementProduct Product
     *
     * @return string|boolean
     */
    protected function _validate($discontinuedProduct, $replacementProduct)
    {
        if ($discontinuedProduct->getId() === $replacementProduct->getId()) {
            return __('Cannot update 2 products with the same ID');
        } elseif (!$replacementProduct->isSalable()) {
            return __('Replacement product is not sale-able. Please check again');
        } elseif ($replacementProduct->isDisabled()) {
            return __('Replacement products should be enabled');
        } elseif ($discontinuedProduct->getDeliveryType() != $replacementProduct->getDeliveryType()) {
            return __("The replace operation wasn't successful, `delivery_type` in target SKU must be the same");
        }

        /*discontinued product - final price*/
        $discontinuedProductPrice = $this->getProductFinalPrice($discontinuedProduct);

        /*replacement product - final price*/
        $replacementProductPrice = $this->getProductFinalPrice($replacementProduct);

        if ($discontinuedProductPrice !== $replacementProductPrice) {
            return __('Products should have the same price');
        }
        if ($discontinuedProduct->getCaseDisplay() != $replacementProduct->getCaseDisplay()) {
            return __('Products should have the same piece case display');
        }
        $bundleValidationResult = $this->validateBundleReplacing($discontinuedProduct, $replacementProduct);
        if (!$bundleValidationResult) {
            return __('Bundle price must be fixed type');
        }
        return false;
    }

    /**
     * @param $productId
     * @return bool
     */
    protected function canDiscontinuedProduct($productId)
    {
        return $this->discontinuedHelper->canDiscontinuedProduct($productId);
    }

    /**
     * get product final price
     *
     * @param $product
     * @return float|\Magento\Framework\Pricing\Amount\AmountInterface
     */
    protected function getProductFinalPrice($product)
    {
        $finalPrice =  $product->getPriceInfo()->getPrice(\Magento\Catalog\Pricing\Price\FinalPrice::PRICE_CODE);
        $amount = ($finalPrice instanceof \Magento\Catalog\Pricing\Price\FinalPrice)
            ? $finalPrice->getAmount()
            : $product->getFinalPrice();

        $price = ($amount instanceof \Magento\Framework\Pricing\Amount\AmountInterface)
            ? $amount->getValue()
            : $amount;

        // check product CS
        if ($product->getCaseDisplay() == 2 && !empty($product->getUnitQty()) && $product->getUnitQty() > 0) {
            $price *= $product->getUnitQty();
        }

        $productPrice = $this->priceCurrency->format(
            $price,
            false,
            \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION
        );

        return $this->localeFormat->getNumber($productPrice);
    }

    /**
     * @param $sku
     */
    protected function addError($sku)
    {
        $this->messageManager->addError(__("No product found, Please check sku %1 again.", $sku));
    }

    /**
     * @param Product $discontinuedProduct
     * @param Product $replacementProduct
     * @return bool
     */
    protected function validateBundleReplacing($discontinuedProduct, $replacementProduct)
    {
        if ($discontinuedProduct->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
            if ($discontinuedProduct->getPriceType() != \Magento\Bundle\Model\Product\Price::PRICE_TYPE_FIXED) {
                return false;
            }
        }
        if ($replacementProduct->getTypeId() == \Magento\Bundle\Model\Product\Type::TYPE_CODE) {
            if ($replacementProduct->getPriceType() != \Magento\Bundle\Model\Product\Price::PRICE_TYPE_FIXED) {
                return false;
            }
        }
        return true;
    }
}
