<?php


namespace Riki\Wamb\Model;

use Riki\Wamb\Api\Data\RuleInterface;

class Rule extends \Magento\Framework\Model\AbstractModel implements RuleInterface
{
    /**
     * @var \Riki\Framework\Helper\Cache\FunctionCache
     */
    protected $functionCache;

    /**
     * Rule constructor.
     *
     * @param \Riki\Framework\Helper\Cache\FunctionCache $functionCache
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Riki\Framework\Helper\Cache\FunctionCache $functionCache,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->functionCache = $functionCache;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function _construct()
    {
        $this->_init(\Riki\Wamb\Model\ResourceModel\Rule::class);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRuleId()
    {
        return $this->getData(self::RULE_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $ruleId
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function setRuleId($ruleId)
    {
        return $this->setData(self::RULE_ID, $ruleId);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $name
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getIsActive()
    {
        return $this->getData(self::IS_ACTIVE);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $is_active
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getMinPurchaseQty()
    {
        return $this->getData(self::MIN_PURCHASE_QTY);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $min_purchase_qty
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function setMinPurchaseQty($minPurchaseQty)
    {
        return $this->setData(self::MIN_PURCHASE_QTY, $minPurchaseQty);
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
     * @param string $createdAt
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $updatedAt
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function getCategoryIds()
    {
        if ($this->hasData(self::CATEGORY_IDS)) {
            return $this->getData(self::CATEGORY_IDS);
        }

        $cacheKey = [$this->getRuleId()];
        if ($this->functionCache->has($cacheKey)) {
            $result = $this->functionCache->load($cacheKey);
            $this->setData(self::CATEGORY_IDS, $result);
            return $result;
        }

        $conn = $this->getResource()->getConnection();
        $select = $conn->select()
            ->from($conn->getTableName('riki_wamb_rule_category'), ['category_id'])
            ->where('rule_id = ?', (int)$this->getRuleId());

        $result = $conn->fetchCol($select);
        $this->functionCache->store($result, $cacheKey);
        $this->setData(self::CATEGORY_IDS, $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param string[] $categoryIds
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function setCategoryIds($categoryIds)
    {
        return $this->setData(self::CATEGORY_IDS, $categoryIds);
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function getCourseIds()
    {
        if ($this->hasData('course_ids')) {
            return $this->getData('course_ids');
        }

        $cacheKey = [$this->getRuleId()];
        if ($this->functionCache->has($cacheKey)) {
            $result = $this->functionCache->load($cacheKey);
            $this->setData('course_ids', $result);
            return $result;
        }

        $conn = $this->getResource()->getConnection();

        $select = $conn->select()
            ->from($conn->getTableName('riki_wamb_rule_course'), ['course_id'])
            ->where('rule_id = ?', (int)$this->getRuleId());

        $result = $conn->fetchCol($select);

        $this->functionCache->store($result, $cacheKey);
        $this->setData('course_ids', $result);

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param string[] $courseIds
     *
     * @return \Riki\Wamb\Api\Data\RuleInterface
     */
    public function setCourseIds($courseIds)
    {
        return $this->setData(self::COURSE_IDS, $courseIds);
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function _afterLoad()
    {
        $this->setOrigData('category_ids', $this->getCategoryIds());
        $this->setOrigData('course_ids', $this->getCourseIds());

        return parent::_afterLoad();
    }
}
