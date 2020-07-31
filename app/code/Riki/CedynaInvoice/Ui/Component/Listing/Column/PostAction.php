<?php
namespace Riki\CedynaInvoice\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class PostAction
 * @package Riki\CedynaInvoice\Ui\Component\Listing\Column
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
            if (!isset($item['id'])) {
                continue;
            }
            $item[$name]['edit'] = [
                'href' => $this->context->getUrl('cedyna_invoice/invoice/edit', [
                    'id' => $item['id']
                ]),
                'label' => __('Edit'),
            ];
            $item[$name]['delete'] = [
                'href' => $this->context->getUrl('cedyna_invoice/invoice/delete', [
                    'id' => $item['id']
                ]),
                'label' => __('Delete'),
                'confirm' => [
                    'title' => __('Are you sure want to do this?'),
                    'message' => __('Are you sure you want to delete "%1" item?', ['${ $.$data.product_line_name }'])
                ]
            ];
        }

        return $dataSource;
    }
}
