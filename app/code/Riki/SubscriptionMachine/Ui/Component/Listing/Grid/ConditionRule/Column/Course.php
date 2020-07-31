<?php

namespace Riki\SubscriptionMachine\Ui\Component\Listing\Grid\ConditionRule\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\System\Store as SystemStore;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Course
 * @package Riki\SubscriptionMachine\Ui\Component\Listing\Grid\ConditionRule\Column
 */
class Course extends Column
{
    /**
     * Escaper
     *
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * System store
     *
     * @var SystemStore
     */
    protected $systemStore;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param SystemStore $systemStore
     * @param Escaper $escaper
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SystemStore $systemStore,
        Escaper $escaper,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        array $components = [],
        array $data = []
    ) {
        $this->systemStore = $systemStore;
        $this->escaper = $escaper;
        $this->courseFactory = $courseFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$this->getData('name')] = $this->prepareItem($item[$this->getData('name')]);
            }
        }

        return $dataSource;
    }

    /**
     * Prepare Item
     *
     * @param string $courseCode
     * @return string
     */
    public function prepareItem($courseCode)
    {
        $courseCode = \Zend_Json::decode($courseCode);
        if (sizeof($courseCode) > 0) {
            $courseModel = $this->courseFactory->create()->getCollection();
            if (sizeof($courseCode) > 0) {
                $courseModel->addFieldToFilter('course_code', $courseCode);
            }
            $courseName = [];
            foreach ($courseModel as $item) {
                $courseName[] = $item->getData('course_code') . " - " . $item->getData('course_name');
            }
            return implode(', ', $courseName);
        }

        return null;
    }
}
