<?php
namespace Riki\Subscription\Model\Landing;

/**
 * Class Campaign
 * @package Riki\Subscription\Model\Landing
 */
class Page extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{
    const FIELD_CATEGORY_IDS = 'category_ids';
    const FIELD_LANDING_PAGE_ID = 'landing_page_id';
    const FIELD_EXCLUDED_COURSES = 'course_ids';
    const TABLE_CATEGORY_ASSOCIATED_NAME = 'subscription_landing_category';
    const TABLE_COURSE_ASSOCIATED_NAME = 'subscription_landing_exclude_course';
    const CACHE_TAG='sub_l_p';
    const FIELD_LANDING_PAGE_NAME = 'name';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Subscription\Model\ResourceModel\Landing\Page');
    }

    /**
     * {}
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
            ->where('landing_page_id = ?', (int)$this->getLandingPageId());
        $result = $conn->fetchCol($select);
        $this->setData(self::FIELD_CATEGORY_IDS, $result);
        return $result;
    }

    /**
     * @param $categoryIds
     * @return \Riki\Subscription\Model\Landing\Page
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
            ->where('landing_page_id = ?', (int)$this->getLandingPageId());
        $result = $conn->fetchCol($select);
        $this->setData(self::FIELD_EXCLUDED_COURSES, $result);
        return $result;
    }

    /**
     * @param $courseIds
     * @return \Riki\Subscription\Model\Landing\Page
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
    public function getLandingPageId()
    {
        return $this->getData(self::FIELD_LANDING_PAGE_ID);
    }

    /**
     * @return mixed
     */
    public function getLandingPageName()
    {
        return $this->getData(self::FIELD_LANDING_PAGE_NAME);
    }

    /**
     * @return array
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

}
