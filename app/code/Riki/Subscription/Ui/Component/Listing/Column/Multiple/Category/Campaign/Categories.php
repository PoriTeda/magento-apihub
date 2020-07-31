<?php
namespace Riki\Subscription\Ui\Component\Listing\Column\Multiple\Category\Campaign;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Categories
 * @package Riki\Subscription\Ui\Component\Listing\Column
 */
class Categories extends Column
{
    /**
     * Class container
     *
     * @var UrlInterface
     */
    protected $urlBuilder;
    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;
    /**
     * @var \Riki\Subscription\Model\Multiple\Category\CampaignFactory
     */
    protected $campaignFactory;

    /**
     * CategorySpot constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Riki\Subscription\Model\Multiple\Category\CampaignFactory $campaignFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Riki\Subscription\Model\Multiple\Category\CampaignFactory $campaignFactory,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->categoryRepository = $categoryRepository;
        $this->campaignFactory = $campaignFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['campaign_id'])) {
                continue;
            }
            $campaignModel = $this->campaignFactory->create()->load($item['campaign_id']);
            $categories = $campaignModel->getData('category_ids');
            if ($categories) {
                $categoryNames = [];
                foreach ($categories as $catId) {
                    $categoryNames[] = $this->categoryRepository->get($catId)->getName();
                }
                $item['category_ids'] = implode(', ', $categoryNames);
            }
        }
        return $dataSource;
    }
}
