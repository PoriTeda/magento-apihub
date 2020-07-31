<?php
namespace Riki\Checkout\Observer;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

class ValidateOrderTotalAmountThreshold implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Riki\SubscriptionCourse\Api\CourseRepositoryInterface
     */
    protected $courseRepository;

    /**
     * @var \Riki\Subscription\Helper\Order
     */
    protected $orderAmountRestriction;

    /**
     * @var DataObject
     */
    protected $dataObj;

    /**
     * ValidateOrderTotalAmountThreshold constructor.
     * @param \Riki\Subscription\Helper\Order $orderAmountRestriction
     * @param \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository
     * @param DataObject $dataObj
     */
    public function __construct(
        \Riki\Subscription\Helper\Order $orderAmountRestriction,
        \Riki\SubscriptionCourse\Api\CourseRepositoryInterface $courseRepository,
        DataObject $dataObj
    ) {
        $this->orderAmountRestriction = $orderAmountRestriction;
        $this->courseRepository = $courseRepository;
        $this->dataObj = $dataObj;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this|void
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();
        if (!$quote instanceof \Magento\Quote\Model\Quote) {
            return $this;
        }
        if ($quote->getData('is_simulator') || $quote->getData('is_generate')) {
            return $this;
        }
        $order = $observer->getEvent()->getOrder();
        if (!$order instanceof \Magento\Sales\Model\Order) {
            return $this;
        }
        if ($quote->getIsOosOrder()) {
            return $this;
        }
        $subCourseId = $quote->getData('riki_course_id');
        if ($subCourseId > 0) {
            $result = $this->orderAmountRestriction->validateAmountFirstOrderSimulator($quote);
            if (isset($result['status']) && !$result['status']) {
                throw new LocalizedException($result['message']);
            }
        }
    }
}
