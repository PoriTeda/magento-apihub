<?php

namespace Riki\Subscription\Controller\Adminhtml\Replace;

class Delete extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;
    /**
     * @var \Riki\Subscription\Model\Profile\ProfileFactory
     */
    protected $profileFactory;
    /**
     * @var \Riki\Subscription\Helper\DiscontinuedHelper
     */
    protected $discontinuedHelper;
    /**
     * @var \Riki\Subscription\Logger\LoggerReplaceProduct
     */
    protected $logger;

    /**
     * Delete constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory
     * @param \Riki\Subscription\Helper\DiscontinuedHelper $discontinuedHelper
     * @param \Riki\Subscription\Logger\LoggerReplaceProduct $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\Subscription\Model\Profile\ProfileFactory $profileFactory,
        \Riki\Subscription\Helper\DiscontinuedHelper $discontinuedHelper,
        \Riki\Subscription\Logger\LoggerReplaceProduct $logger
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->courseFactory = $courseFactory;
        $this->profileFactory = $profileFactory;
        $this->discontinuedHelper = $discontinuedHelper;
        $this->logger = $logger;
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(
            \Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT
        );
        $resultRedirect->setPath('*/*/index');

        if (!$this->getRequest()->isPost() ||
            empty($dis = $this->getRequest()->getParam('replace_discontinue_product', false))
        ) {
            $this->messageManager->addError(__('Invalid input parameters'));
            return $resultRedirect;
        }

        /*get product info*/
        $product = $this->_loadByIdOrSku($dis);

        if (!$product) {
            $this->messageManager->addError(__('Invalid discontinued product'));
            return $resultRedirect;
        }

        /* check this product is assigned to any subscription course or subscription profile */
        if (!$this->discontinuedHelper->canDiscontinuedProduct($product->getId())) {
            $this->messageManager->addError(__("Selected product is not assigned to any Subscription Course"));
            return $resultRedirect;
        }

        /*subscription course model*/
        $courseModel = $this->courseFactory->create();

        /* Delete product in all category */
        $courseModel->deleteProductInCategory($product->getId());

        /*subscription profile model*/
        $profileModel = $this->profileFactory->create();

        /* delete product in all subscription profiles */
        $rs = $profileModel->deleteProfileProduct($product->getId());

        if ($rs['success']) {
            $this->messageManager->addSuccess(
                __('Delete product %1 success.', $product->getName())
            );

            /* we check and send mail before the database has been updated */
            $sendEmail = $this->getRequest()->getParam('replace_send_email', false);
            if ($sendEmail) {
                $emails = $profileModel->sendNotificationEmailDeleteProduct(
                    $rs['success'],
                    $product->getName()
                );

                if ($emails) {
                    $this->logger->info('send mail successfully for: '. implode(', ', $emails));
                }
            }
        }

        if ($rs['fail']) {
            $this->messageManager->addError(__(
                'Cannot delete product %1 from %2 profile(s).',
                $product->getName(),
                count($rs['fail'])
            ));
        }

        if (empty($rs['success']) && empty($rs['fail'])) {
            $this->messageManager->addError(
                __("Selected product %1 is not assigned to any profile.", $product->getName())
            );
        }

        return $resultRedirect;
    }

    /**
     * load product entity based ID or SKU
     *
     * @param $param string ID or SKU
     * @return \Magento\Catalog\Model\Product
     */
    protected function _loadByIdOrSku($param)
    {
        $product = $this->getProduct($param);

        if (empty($product)) {
            $product = $this->getProduct($param, false);
        }

        return $product;
    }

    /**
     * @param $param
     * @param bool $entityId
     * @return bool|\Magento\Catalog\Api\Data\ProductInterface
     */
    protected function getProduct($param, $entityId = true)
    {
        try {
            if ($entityId) {
                /*load product by id*/
                return $this->productRepository->getById($param);
            } else {
                /*load product by sku*/
                return $this->productRepository->get($param);
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
            return false;
        }
    }
}
