<?php
namespace Riki\MachineApi\Ui\Component\Listing\Grid\B2C\Column;

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
            if (!isset($item['type_id'])) {
                continue;
            }
            $item[$name]['edit'] = [
                'href' => $this->context->getUrl('machine/b2c/edit', [
                    'type_id' => $item['type_id']
                ]),
                'label' => __('Edit'),
            ];
            $item[$name]['delete'] = [
                'href' => $this->context->getUrl('machine/b2c/delete', [
                    'type_id' => $item['type_id']
                ]),
                'label' => __('Delete'),
                'confirm' => [
                    'title' => __('Are you sure want to do this?'),
                    'message' => __(
                        'Are you sure you want to delete a "%1" item?',
                        ['${ $.$data.type_code }']
                    )
                ]
            ];
        }

        return $dataSource;
    }
}
