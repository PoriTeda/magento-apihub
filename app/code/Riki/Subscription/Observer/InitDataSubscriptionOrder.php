<?php
namespace Riki\Subscription\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Riki\Subscription\Helper\Order\Data as SubscriptionOrderHelper;

class InitDataSubscriptionOrder implements ObserverInterface
{
    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $subPageHelper;

    /**
     * @var \Riki\SubscriptionCourse\Helper\ValidateDelayPayment
     */
    protected $helperDelayPayment;

    protected $registry;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * InitDataSubscriptionOrder constructor.
     * @param \Riki\SubscriptionPage\Helper\Data $subPageHelper
     * @param \Riki\SubscriptionCourse\Helper\ValidateDelayPayment $helperDelayPayment
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Riki\SubscriptionPage\Helper\Data $subPageHelper,
        \Riki\SubscriptionCourse\Helper\ValidateDelayPayment $helperDelayPayment,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
    ) {
        $this->helperDelayPayment = $helperDelayPayment;
        $this->subPageHelper = $subPageHelper;
        $this->subscriptionValidator = $subscriptionValidator;
    }

    /**
     * @param EventObserver $observer
     * @throws LocalizedException
     */
    public function execute(EventObserver $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        /** @var $quote \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();

        if (!$quote->getData('is_simulator')) {
            if (!$quote->getIsOosOrder() and
                $quote->getData('riki_course_id') and
                $quote->getData('riki_frequency_id') and
                !$quote->getData(SubscriptionOrderHelper::IS_PROFILE_GENERATED_ORDER_KEY)
            ) {
                $validateSubCourse = $this->subPageHelper->validateSubscriptionRule($quote);

                $courseName = null;
                $courseModel = $this->subPageHelper->getSubscriptionCourseModelFromCourseId(
                    $quote->getData('riki_course_id')
                );
                if ($courseModel->getId()) {
                    $courseName = $courseModel->getData('course_name');
                }
                $categoryName = $this->subPageHelper->getCategoryNameMustSkuInSubCourse($courseModel);
                switch ($validateSubCourse) {
                    case 3:
                        $messageError = __("You need to purchase items of %1", $categoryName);
                        throw new LocalizedException($messageError);
                    case 4:
                        $messageError = __(
                            "In %1, the total number of items in the shopping cart have at least %2 quantity",
                            $courseName,
                            $courseModel->getData('minimum_order_qty')
                        );
                        throw new LocalizedException($messageError);
                    case 5:
                        $messageError = __("You need to purchase items of %1", $categoryName);
                        throw new LocalizedException($messageError);
                    default:
                        // Do nothing
                }

                /** Validate maximum qty restriction */
                $prepareData = $this->subscriptionValidator->prepareProductDataByQuote($quote);
                $validateMaximumQty = $this->subscriptionValidator
                    ->setCourseId($quote->getRikiCourseId())
                    ->setProductCarts($prepareData)
                    ->validateMaximumQtyRestriction();

                if ($validateMaximumQty['error']) {
                    $message = $this->subscriptionValidator->getMessageMaximumError(
                        $validateMaximumQty['product_errors'],
                        $validateMaximumQty['maxQty']
                    );
                    throw new LocalizedException($message);
                }
            }

            $order->setData(
                SubscriptionOrderHelper::IS_PROFILE_GENERATED_ORDER_KEY,
                $quote->getData(SubscriptionOrderHelper::IS_PROFILE_GENERATED_ORDER_KEY)
            );

            if ($quote->getData(SubscriptionOrderHelper::IS_PROFILE_GENERATED_ORDER_KEY)) {
                $order->setData(SubscriptionOrderHelper::IS_INCOMPLETE_GENERATE_PROFILE_ORDER, 1);
            }
        }

        $order->setData(
            SubscriptionOrderHelper::SUBSCRIPTION_PROFILE_ID_FIELD_NAME,
            $quote->getData(SubscriptionOrderHelper::SUBSCRIPTION_PROFILE_ID_FIELD_NAME)
        );
        $order->setData(
            SubscriptionOrderHelper::ASSIGNED_WAREHOUSE_ID_KEY,
            $quote->getData(SubscriptionOrderHelper::ASSIGNED_WAREHOUSE_ID_KEY)
        );

        $order->setData('allow_choose_delivery_date', $quote->getAllowChooseDeliveryDate());
    }
}
