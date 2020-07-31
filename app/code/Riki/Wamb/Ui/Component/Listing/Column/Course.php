<?php
namespace Riki\Wamb\Ui\Component\Listing\Column;

class Course extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var \Riki\Wamb\Model\Config\Source\CourseIds
     */
    protected $courseIdsSource;

    /**
     * @var \Riki\Wamb\Model\RuleRepository
     */
    protected $ruleRepository;

    /**
     * Course constructor.
     *
     * @param \Riki\Wamb\Model\Config\Source\CourseIds $courseIdsSource
     * @param \Riki\Wamb\Model\RuleRepository $ruleRepository
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Riki\Wamb\Model\Config\Source\CourseIds $courseIdsSource,
        \Riki\Wamb\Model\RuleRepository $ruleRepository ,
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->courseIdsSource = $courseIdsSource;
        $this->ruleRepository = $ruleRepository;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }


    /**
     * {@inheritdoc}
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items']) && is_array($dataSource['data']['items'])) {
            return $dataSource;
        }

        $courseData = $this->courseIdsSource->toArray();

        foreach ($dataSource['data']['items'] as &$item) {
            if (empty($item['rule_id']) || !empty($item['course_name'])) {
                continue;
            }

            $courseIds = $this->ruleRepository->createFromArray($item)->getCourseIds();

            $courseNames = array_intersect_key($courseData, array_flip($courseIds));

            $item['course_name'] = implode(', ', $courseNames);
        }

        return $dataSource;
    }
}
