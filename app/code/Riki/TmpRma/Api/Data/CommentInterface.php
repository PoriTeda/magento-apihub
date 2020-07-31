<?php
/**
 * TmpRma
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\TmpRma
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\TmpRma\Api\Data;

/**
 * Interface CommentInterface
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Api
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
interface CommentInterface
{
    /**#@+
     * Constants for keys of data array.
     * Identical to the name of the getter in snake case.
     */
    const ENTITY_ID = 'entity_id';
    const PARENT_ID = 'parent_id';
    const IS_CUSTOMER_NOTIFIED = 'is_customer_notified';
    const IS_VISIBLE_ON_FRONT = 'is_visible_on_front';
    const COMMENT = 'comment';
    const CREATED_AT = 'created_at';

    /**
     * Get entity id
     *
     * @return int
     */
    public function getEntityId();

    /**
     * Set entity id
     *
     * @param string|int $entityId entityId
     *
     * @return $this
     */
    public function setEntityId($entityId);

    /**
     * Get parent id
     *
     * @return int
     */
    public function getParentId();

    /**
     * Set parent id
     *
     * @param string|int $parentId parentId
     *
     * @return $this
     */
    public function setParentId($parentId);

    /**
     * Get is_customer_notified flag
     *
     * @return int
     */
    public function getIsCustomerNotified();

    /**
     * Set is_customer_notified flag
     *
     * @param string|int $isCustomerNotified isCustomerNotified
     *
     * @return $this
     */
    public function setIsCustomerNotified($isCustomerNotified);

    /**
     * Get is_visible_on_front flag
     *
     * @return int
     */
    public function getIsVisibleOnFront();

    /**
     * Set is_visible_on_front flag
     *
     * @param string|int $isVisibleOnFront isVisibleOnFront
     *
     * @return $this
     */
    public function setIsVisibleOnFront($isVisibleOnFront);

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment();

    /**
     * Set comment
     *
     * @param string $comment comment
     *
     * @return $this
     */
    public function setComment($comment);

    /**
     * Get created at
     *
     * @return int
     */
    public function getCreatedAt();

    /**
     * Set created at
     *
     * @param string $createdAt createAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt);

}
