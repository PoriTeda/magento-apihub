<?php
namespace Riki\Wamb\Ui\Component\Listing\Column;

class Category extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var \Riki\Wamb\Model\Config\Source\CategoryIds
     */
    protected $categoryIdsSource;

    /**
     * @var \Riki\Wamb\Model\RuleRepository
     */
    protected $ruleRepository;

    /**
     * Category constructor.
     *
     * @param \Riki\Wamb\Model\RuleRepository $ruleRepository
     * @param \Riki\Wamb\Model\Config\Source\CategoryIds $categoryIdsSource
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Riki\Wamb\Model\RuleRepository $ruleRepository,
        \Riki\Wamb\Model\Config\Source\CategoryIds $categoryIdsSource,
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->ruleRepository = $ruleRepository;
        $this->categoryIdsSource = $categoryIdsSource;
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
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $categoryData = $this->categoryIdsSource->toArray();

        foreach ($dataSource['data']['items'] as &$item) {
            if (empty($item['rule_id']) || !empty($item['category_name'])) {
                continue;
            }

            $categoryIds = $this->ruleRepository->createFromArray($item)->getCategoryIds();

            $categoryNames = array_intersect_key($categoryData, array_flip($categoryIds));

            $item['category_name'] = implode(', ', $categoryNames);
        }

        return $dataSource;
    }
}
