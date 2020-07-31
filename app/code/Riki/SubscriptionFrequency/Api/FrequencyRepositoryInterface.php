<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\SubscriptionFrequency\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Frequency page CRUD interface.
 * @api
 */
interface FrequencyRepositoryInterface
{
    /**
     * Save page.
     *
     * @param \Riki\SubscriptionFrequency\Api\Data\FrequencyInterface $page
     * @return \Riki\SubscriptionFrequency\Api\Data\FrequencyInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Riki\SubscriptionFrequency\Api\Data\FrequencyInterface $page);

    /**
     * Retrieve page.
     *
     * @param int $pageId
     * @return \Riki\SubscriptionFrequency\Api\Data\FrequencyInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($pageId);

    /**
     * Retrieve pages matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Riki\SubscriptionFrequency\Api\Data\PageSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete page.
     *
     * @param \Riki\SubscriptionFrequency\Api\Data\FrequencyInterface $page
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(\Riki\SubscriptionFrequency\Api\Data\FrequencyInterface $page);

    /**
     * Delete page by ID.
     *
     * @param int $pageId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($pageId);
}
