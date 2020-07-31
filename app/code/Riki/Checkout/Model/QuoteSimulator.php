<?php

namespace Riki\Checkout\Model;

use Magento\Framework\Convert\DataObject;
use Magento\Framework\Exception\ValidatorException;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;
use Riki\Subscription\Model\Constant;

class QuoteSimulator
{
    /**
     * @var \Magento\Quote\Model\QuoteFactory
     */
    protected $quoteFactory;

    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
     */
    protected $courseRepository;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Riki\Subscription\Helper\Hanpukai\Data
     */
    protected $hanpukaiHelper;

    /**
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $productFactory;
    /**
     * Logger
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * QuoteSimulator constructor.
     *
     * @param \Magento\Quote\Model\QuoteFactory $quoteFactory
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Quote\Model\QuoteFactory $quoteFactory,
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository,
        \Magento\Framework\Registry $coreRegistry,
        \Riki\Subscription\Helper\Hanpukai\Data $hanpukaiHelper,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->quoteFactory = $quoteFactory;
        $this->courseRepository = $courseRepository;
        $this->coreRegistry = $coreRegistry;
        $this->hanpukaiHelper = $hanpukaiHelper;
        $this->productFactory = $productFactory;
        $this->logger = $logger;
    }

    /**
     * @param int $quoteId
     * @param int $nthDelivery
     *
     * @return \Magento\Quote\Model\Quote|null
     * @throws ValidatorException
     */
    public function simulateQuoteByNthDelivery($quoteId, $nthDelivery)
    {
        $quote = $this->quoteFactory->create()->load($quoteId);

        if (!$quote->getId()) {
            return null;
        }

        if (1 == $nthDelivery || $quote->getData('n_delivery') == $nthDelivery) {
            throw new ValidatorException(__('Invalid Nth Delivery.'));
        }

        if ($courseId = $quote->getRikiCourseId()) {
            $course = $this->courseRepository->get($courseId);
            if ($course->getSubscriptionType() == 'hanpukai'
                && $course->getData('hanpukai_type') == CourseType::TYPE_HANPUKAI_SEQUENCE) {
                // Remove all current item - fake data to use existing function
                $productInfo['profile_id'] = null;
                $firstItem = $quote->getItemsCollection()->getFirstItem();
                $productInfo['shipping_address_id'] = $firstItem->getData('shipping_address_id');
                $productInfo['billing_address_id'] = $firstItem->getData('billing_address_id');
                $productInfo['delivery_date'] = $firstItem->getData('delivery_date');
                $quote->removeAllItems();
                // Get Hanpukai next items using existing function in Hanpukai helper
                $hanpukaiQty = $quote->getData(Constant::RIKI_HANPUKAI_QTY);
                $productDataHanpukaiSequence = $this->hanpukaiHelper->replaceHanpukaiSequenceProduct(
                    $courseId,
                    $nthDelivery,
                    $productInfo,
                    $hanpukaiQty
                );
                // Add next delivery items to cart
                foreach ($productDataHanpukaiSequence as $productData) {
                    try {
                        // NED-1281 Extensional fix for hanpukai CS product -
                        // current helper logic is wrong with qty multiplication
                        // Add fix here to avoid conflict logic with other function
                        if ($productData['unit_case'] == 'CS') {
                            $productData['qty'] = (int) $productData['qty'] / (int) $productData['unit_qty'];
                        }
                        $product = $this->productFactory->create()->load($productData['product_id']);
                        if ($product && $product->getStatus() == 1) {
                            $obj = new \Magento\Framework\DataObject();
                            $obj->setData($productData);
                            $quote->addProduct($product, $obj);
                        }
                    } catch (\Exception $e) {
                        $this->logger->critical(
                            'Cannot add product for cart ID '.$quoteId.
                            ' for Hanpukai Sequence Simulator.  error: '.$e->getMessage()
                        );
                    }
                }
            }
        }

        $this->coreRegistry->unregister('nth_delivery_override');
        $this->coreRegistry->register('nth_delivery_override', $nthDelivery);

        $quote->getShippingAddress()->setCollectShippingRates(true);

        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quote->getAllItems() as $quoteItem) {
            if ($quoteItem->getData('is_riki_machine')) {
                $quote->deleteItem($quoteItem);
            }
        }
        $quote->setData('n_delivery', $nthDelivery)
            ->unsetData('coupon_code')
            ->setData('skip_use_point', true)
            ->setData('skip_earn_point', true)
            ->setTotalsCollectedFlag(false)
            ->collectTotals();

        $this->coreRegistry->unregister('nth_delivery_override');

        return $quote;
    }
}
