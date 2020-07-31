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
namespace Riki\TmpRma\Model\Rma;

/**
 * Class Comment
 *
 * @category  RIKI
 * @package   Riki\TmpRma\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Comment
    extends \Magento\Framework\Model\AbstractModel
    implements \Riki\TmpRma\Api\Data\CommentInterface
{
    /**
     * {@inheritdoc}
     *
     * @return void
     */
    protected function _construct() //@codingStandardsIgnoreLine
    {
        $this->_init('Riki\TmpRma\Model\ResourceModel\Rma\Comment');
    }

    /**
     * {@inheritdoc}
     *
     * @return int|string
     */
    public function getEntityId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $entityId entityId
     *
     * @return $this
     */
    public function setEntityId($entityId)
    {
        $this->setData(self::ENTITY_ID, $entityId);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|string
     */
    public function getParentId()
    {
        return $this->getData(self::PARENT_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $parentId parentId
     *
     * @return $this
     */
    public function setParentId($parentId)
    {
        $this->setData(self::PARENT_ID, $parentId);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|string
     */
    public function getIsCustomerNotified()
    {
        return $this->getData(self::IS_CUSTOMER_NOTIFIED);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $isCustomerNotified isCustomerNotified
     *
     * @return $this
     */
    public function setIsCustomerNotified($isCustomerNotified)
    {
        $this->setData(self::IS_CUSTOMER_NOTIFIED, $isCustomerNotified);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return int|string
     */
    public function getIsVisibleOnFront()
    {
        return $this->getData(self::IS_VISIBLE_ON_FRONT);
    }

    /**
     * {@inheritdoc}
     *
     * @param string|int $isVisibleOnFront isVisibleOnFront
     *
     * @return $this
     */
    public function setIsVisibleOnFront($isVisibleOnFront)
    {
        $this->getData(self::IS_VISIBLE_ON_FRONT, $isVisibleOnFront);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getComment()
    {
        return $this->getData(self::COMMENT);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $comment comment
     *
     * @return $this
     */
    public function setComment($comment)
    {
        $this->setData(self::COMMENT, $comment);
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $createdAt createAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->setData(self::CREATED_AT, $createdAt);
        return $this;
    }
}
