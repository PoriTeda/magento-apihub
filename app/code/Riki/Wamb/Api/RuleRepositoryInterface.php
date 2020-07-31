<?php
namespace Riki\Wamb\Api;


interface RuleRepositoryInterface
{
    /**
     * Save
     *
     * @param \Riki\Wamb\Api\Data\RuleInterface $rule
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\Riki\Wamb\Api\Data\RuleInterface $rule);

    /**
     * Get rule by id
     *
     * @param string $ruleId
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($ruleId);

    /**
     * Get list
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     *
     * @return \Riki\Wamb\Api\Data\RuleSearchResultsInterface
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);


    /**
     * Delete
     *
     * @param \Riki\Wamb\Api\Data\RuleInterface $rule
     *
     * @return bool true on success
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(\Riki\Wamb\Api\Data\RuleInterface $rule);

    /**
     * Delete by ID
     *
     * @param string $ruleId
     *
     * @return bool true on success
     *
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($ruleId);

    /**
     * Create rule from array
     *
     * @param array $data
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function createFromArray($data = []);

}
