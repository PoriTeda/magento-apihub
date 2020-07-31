<?php
namespace Riki\Wamb\Model\ResourceModel\Rule\Grid;

class Collection extends \Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult
{
    /**
     * @var \Riki\Wamb\Model\Config\Source\CategoryIds
     */
    protected $categoryIdsSource;

    /**
     * @var \Riki\Wamb\Model\Config\Source\CourseIds
     */
    protected $courseIdsSource;

    /**
     * Collection constructor.
     *
     * @param \Riki\Wamb\Model\Config\Source\CourseIds $courseIdsSource
     * @param \Riki\Wamb\Model\Config\Source\CategoryIds $categoryIdsSource
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param string $mainTable
     * @param string $resourceModel
     */
    public function __construct(
        \Riki\Wamb\Model\Config\Source\CourseIds $courseIdsSource,
        \Riki\Wamb\Model\Config\Source\CategoryIds $categoryIdsSource,
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        $mainTable = 'riki_wamb_rule',
        $resourceModel = 'Riki\Wamb\Model\ResourceModel\Rule\Collection'
    ) {
        $this->courseIdsSource = $courseIdsSource;
        $this->categoryIdsSource = $categoryIdsSource;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * {@inheritdoc}
     *
     * @param array|string $field
     * @param null $condition
     *
     * @return $this
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if ($field == 'category_name') {
            $categoryData = $this->categoryIdsSource->toArray();
            $categoryName = trim(end($condition), '%');
            $categoryIds = array_filter($categoryData, function ($v) use ($categoryName) { return strpos($v, $categoryName) !== false;});
            $ruleCategoryTb = $this->getTable('riki_wamb_rule_category');
            $this->join(['rca' => $ruleCategoryTb], 'rca.rule_id = main_table.rule_id', []);
            $field = 'rca.category_id';
            $condition = ['in' => array_keys($categoryIds)];
            $this->distinct(true); // @ need improve performance
        }

        if ($field == 'course_name') {
            $courseData = $this->courseIdsSource->toArray();
            $courseName = trim(end($condition), '%');
            $courseIds = array_filter($courseData, function ($v) use ($courseName) { return strpos($v, $courseName) !== false;});
            $ruleCourseTb = $this->getTable('riki_wamb_rule_course');
            $this->join(['rco' => $ruleCourseTb], 'rco.rule_id = main_table.rule_id', []);
            $field = 'rco.course_id';
            $condition = ['in' => array_keys($courseIds)];
            $this->distinct(true); // @ need improve performance
        }

        return parent::addFieldToFilter($field, $condition);
    }
}