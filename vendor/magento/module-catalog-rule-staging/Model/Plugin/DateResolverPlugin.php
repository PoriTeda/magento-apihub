<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Model\Plugin;

use Magento\CatalogRule\Model\Rule;
use Magento\Staging\Api\UpdateRepositoryInterface;

class DateResolverPlugin
{
    /**
     * @param \Magento\Staging\Api\UpdateRepositoryInterface
     */
    protected $updateRepository;

    /**
     * @param \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository
     */
    public function __construct(
        UpdateRepositoryInterface $updateRepository
    ) {
        $this->updateRepository = $updateRepository;
    }

    /**
     * Provide update start date to the rule model.
     *
     * @param \Magento\CatalogRule\Model\Rule $subject
     * @return void
     */
    public function beforeGetFromDate(Rule $subject)
    {
        $subject->setData('from_date', $this->resolveDate($subject)->getStartTime());
    }

    /**
     * Provide update end date to the rule model.
     *
     * @param \Magento\CatalogRule\Model\Rule $subject
     * @return void
     */
    public function beforeGetToDate(Rule $subject)
    {
        $subject->setData('to_date', $this->resolveDate($subject)->getEndTime());
    }

    /**
     * Resolve date using update id.
     * @param \Magento\CatalogRule\Model\Rule $subject
     * @return \Magento\Staging\Api\Data\UpdateInterface
     */
    protected function resolveDate(Rule $subject)
    {
        $campaignId = $subject->getData('campaign_id');
        $versionId = $campaignId === null ? $subject->getData('created_in') : $campaignId;
        return $this->updateRepository->get($versionId);
    }
}
