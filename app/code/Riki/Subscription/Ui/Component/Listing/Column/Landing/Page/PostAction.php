<?php
namespace Riki\Subscription\Ui\Component\Listing\Column\Landing\Page;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class PostAction
 * @package Riki\Riki_Subscription\Ui\Component\Listing\Column
 */
class PostAction extends Column
{
    /**
     * Class container
     *
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param   array $dataSource
     * @return  array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['landing_page_id'])) {
                continue;
            }

            $item[$name]['edit'] = [
                'href' => $this->context->getUrl('subscription/landing_page/edit', [
                    'landing_page_id' => $item['landing_page_id']
                ]),
                'label' => __('Edit'),
            ];
        }

        return $dataSource;
    }
}
