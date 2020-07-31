<?php

namespace Riki\SubscriptionMachine\Ui\Component\Listing\Grid\ConditionRule\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Store\Model\System\Store as SystemStore;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Category
 * @package Riki\SubscriptionMachine\Ui\Component\Listing\Grid\ConditionRule\Column
 */
class Category extends Column
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
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param SystemStore $systemStore
     * @param Escaper $escaper
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        SystemStore $systemStore,
        Escaper $escaper,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        array $components = [],
        array $data = []
    ) {
        $this->systemStore = $systemStore;
        $this->escaper = $escaper;
        $this->categoryRepository = $categoryRepository;
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
     * @param int $categoryId
     * @return string
     * @throws \Exception
     */
    public function prepareItem($categoryId)
    {
        if ($categoryId == 0 || $categoryId == null) {
            return null;
        } else {
            $categoryRepo = $this->categoryRepository->get($categoryId);
            if ($categoryRepo) {
                return $categoryRepo->getName();
            }
        }
    }
}
