<?php
namespace Riki\Customer\Ui\Component\ConsumerDB\Listing\Grid\Column;

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
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')]['edit'] = [
                    'href' => $this->context->getUrl(
                        'customer/consumerdb/detail',
                        ['id' => $item['customer_id']]
                    ),
                    'label' => __('Create/Edit Customer'),
                    'hidden' => false,
                    'target' => '_blank',
                ];
            }
        }
        return $dataSource;
    }
}