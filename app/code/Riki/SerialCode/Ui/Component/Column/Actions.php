<?php

namespace Riki\SerialCode\Ui\Component\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Riki\SerialCode\Model\Source\Status as SerialCodeStatus;

/**
 * Class BlockActions
 */
class Actions extends Column
{
    /**
     * Url path
     */
    const URL_PATH_EDIT = 'serial_code/index/edit';
    const URL_PATH_DELETE = 'serial_code/index/delete';
    const URL_PATH_CANCEL = 'serial_code/index/cancel';

    /**
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
     * @param array $items
     * @return array
     */
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $protect = [SerialCodeStatus::STATUS_USED];
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item['id'])) {
                    if (in_array($item['status'], $protect)) {
                        continue;
                    }
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_EDIT,
                                [
                                    'id' => $item['id']
                                ]
                            ),
                            'label' => __('Edit')
                        ],
                        'delete' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_DELETE,
                                [
                                    'id' => $item['id']
                                ]
                            ),
                            'label' => __('Delete'),
                            'confirm' => [
                                'title' => __('Delete "${ $.$data.serial_code }"'),
                                'message' => __('Are you sure you wan\'t to delete a "${ $.$data.serial_code }" record?')
                            ]
                        ],
                        'cancel' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_CANCEL,
                                [
                                    'id' => $item['id']
                                ]
                            ),
                            'label' => __('Cancel'),
                            'confirm' => [
                                'title' => __('Cancel "${ $.$data.serial_code }"'),
                                'message' => __('Are you sure you wan\'t to cancel a "${ $.$data.serial_code }" record?')
                            ]
                        ]
                    ];
                    if ($item['status'] == SerialCodeStatus::STATUS_CANCELLED) {
                        unset($item[$this->getData('name')]['cancel']);
                    }
                }
            }
        }

        return $dataSource;
    }
}
