<?php
namespace Riki\AdminLog\Ui\Component\Log\Listing\Grid\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class Action extends Column
{
    /**
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {

        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }
        $name = $this->getData('name');
        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['log_id'])) {
                continue;
            }

            $item[$name]['view'] = [
                'href' => $this->context->getUrl('adminlog/log/view', [
                    'log_id' => $item['log_id']
                ]),
                'label' => __('View detail'),
            ];
        }

        return $dataSource;
    }
}