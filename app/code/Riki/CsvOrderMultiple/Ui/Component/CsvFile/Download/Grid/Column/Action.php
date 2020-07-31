<?php

namespace Riki\CsvOrderMultiple\Ui\Component\CsvFile\Download\Grid\Column;

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
            if (!isset($item['entity_id'])) {
                continue;
            }

            $item[$name]['download'] = [
                'href' => $this->context->getUrl('csvOrderMultiple/csv/download', [
                    'entity_id' => $item['entity_id']
                ]),
                'label' => __('Download'),
            ];
        }

        return $dataSource;
    }
}
