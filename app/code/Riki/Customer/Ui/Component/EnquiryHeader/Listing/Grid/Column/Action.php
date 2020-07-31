<?php
namespace Riki\Customer\Ui\Component\EnquiryHeader\Listing\Grid\Column;

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
                        'customer/enquiryheader/edit',
                        ['id' => $item['id']]
                    ),
                    'label' => __('Edit'),
                    'hidden' => false,
                ];
                $item[$this->getData('name')]['delete'] = [
                    'href' => $this->context->getUrl(
                        'customer/enquiryheader/delete',
                        ['id' => $item['id']]
                    ),
                    'label' => __('Delete'),
                    'hidden' => false,
                    'confirm' => [
                        'title' => __('Delete'),
                        'message' => __('Are you sure you wan\'t to delete record?')
                    ]
                ];
            }
        }
        return $dataSource;
    }
}