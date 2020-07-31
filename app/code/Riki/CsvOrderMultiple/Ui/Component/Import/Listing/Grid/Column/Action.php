<?php
namespace Riki\CsvOrderMultiple\Ui\Component\Import\Listing\Grid\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Riki\CsvOrderMultiple\Api\Data\StatusInterface;


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
            if(isset($item['status']) && $item['status'] ==StatusInterface::IMPORT_WAITING)
            {
                $item[$name]['delete'] = [
                    'href' => $this->context->getUrl('csvOrderMultiple/import/delete', [
                        'entity_id' => $item['entity_id']
                    ]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Are you sure want to do this?'),
                        'message' => __('Are you sure you want to delete item?')
                    ]
                ];
            }
        }

        return $dataSource;
    }
}