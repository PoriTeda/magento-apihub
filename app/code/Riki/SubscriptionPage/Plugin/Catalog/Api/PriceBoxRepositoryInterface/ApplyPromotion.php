<?php
namespace Riki\SubscriptionPage\Plugin\Catalog\Api\PriceBoxRepositoryInterface;

class ApplyPromotion
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * ApplyPromotion constructor.
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        $this->registry = $registry;
    }

    /**
     * Registry course_id, frequency_id
     *
     * @param \Riki\Catalog\Api\PriceBoxRepositoryInterface $subject
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @return array
     */
    public function beforeGetList(\Riki\Catalog\Api\PriceBoxRepositoryInterface $subject, \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria)
    {
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() == 'course_id' && $filter->getConditionType() == 'eq') {
                    $this->registry->unregister(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID);
                    $this->registry->register(\Riki\Subscription\Model\Constant::RIKI_COURSE_ID, $filter->getValue());
                }

                if ($filter->getField() == 'frequency_id' && $filter->getConditionType() == 'eq') {
                    $this->registry->unregister(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID);
                    $this->registry->register(\Riki\Subscription\Model\Constant::RIKI_FREQUENCY_ID, $filter->getValue());
                }
            }
        }

        return [$searchCriteria];
    }
}