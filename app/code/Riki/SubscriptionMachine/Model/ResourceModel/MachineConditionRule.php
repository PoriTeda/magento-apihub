<?php

namespace Riki\SubscriptionMachine\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;

class MachineConditionRule extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Riki\SubscriptionFrequency\Model\FrequencyFactory
     */
    protected $frequencyFactory;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\SubscriptionFrequency\Model\FrequencyFactory $frequencyFactory,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        $connectionName = null
    ) {
        $this->courseFactory = $courseFactory;
        $this->frequencyFactory = $frequencyFactory;
        $this->categoryRepository = $categoryRepository;
        parent::__construct($context, $connectionName);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('riki_machine_condition', 'id');
    }

    /**
     * @param AbstractModel $object
     * @return \Magento\Framework\Model\ResourceModel\Db\AbstractDb
     * @throws \Zend_Json_Exception
     */
    protected function _afterLoad(AbstractModel $object)
    {
        $object->setData('frequency_label', $this->getFrequencyLabel($object->getData('frequency')));
        $object->setData('course_name', $this->getCourseName($object->getData('course_code')));
        $object->setData('category_name', $this->getCategoryName($object->getData('category_id')));
        return parent::_afterLoad($object);
    }

    /**
     * @param $frequency
     * @return null|string
     * @throws \Zend_Json_Exception
     */
    public function getFrequencyLabel($frequency)
    {
        $frequency = \Zend_Json::decode($frequency);
        if (sizeof($frequency) > 0) {
            $frequencyModel = $this->frequencyFactory->create()->getCollection();
            if (sizeof($frequency) > 0) {
                $frequencyModel->addFieldToFilter('frequency_id', $frequency);
            }
            $frequencyLabel = [];
            foreach ($frequencyModel as $item) {
                $frequencyLabel[] = $item->getData('frequency_interval') . " " . $item->getData('frequency_unit');
            }
            return implode(', ', $frequencyLabel);
        }
        return null;
    }

    /**
     * @param $courseCode
     * @return null|string
     * @throws \Zend_Json_Exception
     */
    public function getCourseName($courseCode)
    {
        $courseCode = \Zend_Json::decode($courseCode);
        if (sizeof($courseCode) > 0) {
            $courseModel = $this->courseFactory->create()->getCollection();
            if (sizeof($courseCode) > 0) {
                $courseModel->addFieldToFilter('course_code', $courseCode);
            }
            $courseName = [];
            foreach ($courseModel as $item) {
                $courseName[] = $item->getData('course_name');
            }
            return implode(', ', $courseName);
        }
        return null;
    }

    /**
     * @param $categoryId
     * @return null|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getCategoryName($categoryId)
    {
        if ($categoryId == 0 || $categoryId == null) {
            return null;
        }
        $categoryRepo = $this->categoryRepository->get($categoryId);
        if ($categoryRepo) {
            return $categoryRepo->getName();
        }
    }
}
