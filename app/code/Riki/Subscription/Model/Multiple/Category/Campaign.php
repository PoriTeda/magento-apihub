<?php
namespace Riki\Subscription\Model\Multiple\Category;

/**
 * Class Campaign
 * @package Riki\Subscription\Model\Multiple\Category
 */
class Campaign extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const FIELD_CATEGORY_IDS = 'category_ids';
    const FIELD_CAMPAIGN_ID = 'campaign_id';
    const FIELD_EXCLUDED_COURSES = 'course_ids';
    const TABLE_CATEGORY_ASSOCIATED_NAME = 'subscription_multiple_category_campaign_category';
    const TABLE_COURSE_ASSOCIATED_NAME = 'subscription_multiple_category_campaign_excluded_course';
    const CACHE_TAG='sub_m_c_c';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\ResourceModel\Multiple\Category\Campaign');
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public function getCategoryIds()
    {
        if ($this->hasData(self::FIELD_CATEGORY_IDS)) {
            return $this->getData(self::FIELD_CATEGORY_IDS);
        }
        $conn = $this->getResource()->getConnection();
        $select = $conn->select()
            ->from($conn->getTableName(self::TABLE_CATEGORY_ASSOCIATED_NAME), ['category_id'])
            ->where('campaign_id = ?', (int)$this->getCampaignId());
        $result = $conn->fetchCol($select);
        $this->setData(self::FIELD_CATEGORY_IDS, $result);
        return $result;
    }

    /**
     * @param $categoryIds
     * @return Campaign
     */
    public function setCategoryIds($categoryIds)
    {
        return $this->setData(self::FIELD_CATEGORY_IDS, $categoryIds);
    }

    /**
     * @return array|mixed
     */
    public function getCourseIds()
    {
        if ($this->hasData(self::FIELD_EXCLUDED_COURSES)) {
            return $this->getData(self::FIELD_EXCLUDED_COURSES);
        }
        $conn = $this->getResource()->getConnection();
        $select = $conn->select()
            ->from($conn->getTableName(self::TABLE_COURSE_ASSOCIATED_NAME), ['course_id'])
            ->where('campaign_id = ?', (int)$this->getCampaignId());
        $result = $conn->fetchCol($select);
        $this->setData(self::FIELD_EXCLUDED_COURSES, $result);
        return $result;
    }

    /**
     * @param $categoryIds
     * @return Campaign
     */
    public function setCourseIds($courseIds)
    {
        return $this->setData(self::FIELD_EXCLUDED_COURSES, $courseIds);
    }

    /**
     * {@inheritdoc}
     *
     * @return $this
     */
    public function _afterLoad()
    {
        $this->setOrigData(self::FIELD_CATEGORY_IDS, $this->getCategoryIds());
        $this->setOrigData(self::FIELD_EXCLUDED_COURSES, $this->getCourseIds());
        return parent::_afterLoad();
    }

    /**
     * @return mixed
     */
    public function getCampaignId()
    {
        return $this->getData(self::FIELD_CAMPAIGN_ID);
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

}
