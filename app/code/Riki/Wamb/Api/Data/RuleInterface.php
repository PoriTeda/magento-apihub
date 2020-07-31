<?php


namespace Riki\Wamb\Api\Data;

interface RuleInterface
{
    const RULE_ID = 'rule_id';
    const NAME = 'name';
    const MIN_PURCHASE_QTY = 'min_purchase_qty';
    const IS_ACTIVE = 'is_active';
    const CATEGORY_IDS = 'category_ids';
    const COURSE_IDS = 'course_ids';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';


    /**
     * Get rule_id
     *
     * @return string|null
     */
    public function getRuleId();

    /**
     * Get name
     *
     * @return string|null
     */
    public function getName();

    /**
     * Set name
     *
     * @param string $name
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function setName($name);

    /**
     * Get is_active
     *
     * @return string|null
     */
    public function getIsActive();

    /**
     * Set is_active
     *
     * @param string $isActive
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function setIsActive($isActive);

    /**
     * Get min_purchase_qty
     *
     * @return string|null
     */
    public function getMinPurchaseQty();

    /**
     * Set min_purchase_qty
     *
     * @param string $minPurchaseQty
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function setMinPurchaseQty($minPurchaseQty);

    /**
     * Get created_at
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_date
     *
     * @param string $createdAt
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get updated_at
     *
     * @return string|null
     */
    public function getUpdatedAt();

    /**
     * Set updated_at
     *
     * @param string $updatedAt
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Get category ids
     *
     * @return string[]
     */
    public function getCategoryIds();

    /**
     * Set category ids
     *
     * @param string[] $categoryIds
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function setCategoryIds($categoryIds);

    /**
     * Get course ids
     *
     * @return string[]
     */
    public function getCourseIds();

    /**
     * Get course ids
     *
     * @param string[] $courseIds
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function setCourseIds($courseIds);
}
