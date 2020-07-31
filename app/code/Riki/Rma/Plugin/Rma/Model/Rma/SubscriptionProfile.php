<?php
namespace Riki\Rma\Plugin\Rma\Model\Rma;

class SubscriptionProfile
{
    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\Subscription\Api\ProfileRepositoryInterface
     */
    protected $profileRepository;

    /**
     * @var \Magento\Sales\Api\OrderItemRepositoryInterface
     */
    protected $orderItemRepository;

    /**
     * SubscriptionProfile constructor.
     *
     * @param \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository
     * @param \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Riki\Rma\Helper\Data $dataHelper
     */
    public function __construct(
        \Magento\Sales\Api\OrderItemRepositoryInterface $orderItemRepository,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Riki\Rma\Helper\Data $dataHelper
    ) {
        $this->orderItemRepository = $orderItemRepository;
        $this->profileRepository = $profileRepository;
        $this->searchHelper = $searchHelper;
        $this->dataHelper = $dataHelper;
    }

    /**
     * Decrease sale_count & sales_count_value
     *
     * @param \Magento\Rma\Model\Rma $subject
     *
     * @return array
     */
    public function beforeAfterSave(\Magento\Rma\Model\Rma $subject)
    {
        if (!$subject->dataHasChangedFor('return_status')) {
            return [];
        }

        if ($subject->getData('return_status') != \Riki\Rma\Api\Data\Rma\ReturnStatusInterface::COMPLETED) {
            return [];
        }

        $order = $this->dataHelper->getRmaOrder($subject);
        if (!$order) {
            return [];
        }


        if (!$order->getData('subscription_profile_id')) {
            return [];
        }

        /** @var \Riki\Subscription\Model\Profile\Profile $profile */
        $profile = $this->searchHelper
            ->getByProfileId($order->getData('subscription_profile_id'))
            ->getOne()
            ->execute($this->profileRepository);
        if (!$profile) {
            return [];
        }

        $course = $profile->getSubscriptionCourse();
        if (!$course || !$course->getId()) {
            return [];
        }

        if (intval($course->getData('sales_count'))
            && ($salesCount = intval($profile->getData('sales_count')))
        ) {
            $orderItemIds = [];
            $orderItems = [];
            foreach ($this->dataHelper->getRmaItems($subject) as $rmaItem) {
                $orderItemIds[$rmaItem->getOrderItemId()] = $rmaItem->getQtyRequested();
            }

            if ($orderItemIds) {
                $orderItems = $this->searchHelper
                    ->getByItemId(array_keys($orderItemIds))
                    ->getAll()
                    ->execute($this->orderItemRepository);
            }
            foreach ($orderItems as $orderItem) {
                if (!isset($orderItemIds[$orderItem->getId()])) {
                    continue;
                }

                if ($orderItem->getData('prize_id') || $orderItem->getData('is_riki_machine')) {
                    continue;
                }

                $buyRequest = $orderItem->getBuyRequest();
                if (isset($buyRequest['options']['ampromo_rule_id'])) {
                    continue;
                }
                $salesCount -= floatval($orderItemIds[$orderItem->getId()]);
            }
            $salesCount = $salesCount < 0 ? 0 : $salesCount;
            $profile->setData('sales_count', $salesCount);
        }

        if (floatval($course->getData('sales_value_count'))
            && ($salesValueCount = floatval($profile->getData('sales_value_count')))
        ) {
            $salesValueCount -= floatval($subject->getData('total_return_amount_adjusted'));
            $salesValueCount = $salesValueCount < 0 ? 0 : $salesValueCount;
            $profile->setData('sales_value_count', $salesValueCount);
        }

        if ($profile->hasDataChanges()) {
            $this->profileRepository->save($profile->getDataModel());
        }

        return [];
    }
}