<?php
namespace Bluecom\Paygent\Ui\Component\Error\Listing\Grid\Column;

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
            if (!isset($item['error_id'])) {
                continue;
            }

            $item[$name]['view'] = [
                'href' => $this->context->getUrl('paygent/error/edit', [
                    'error_id' => $item['error_id']
                ]),
                'label' => __('View detail'),
            ];
        }

        return $dataSource;
    }
}