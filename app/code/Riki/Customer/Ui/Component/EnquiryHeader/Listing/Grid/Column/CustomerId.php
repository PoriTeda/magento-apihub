<?php
namespace Riki\Customer\Ui\Component\EnquiryHeader\Listing\Grid\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class CustomerId extends Column
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
                $item[$this->getData('name')] ="<a href='". $this->context->getUrl('customer/index/edit',['id' => $item[$this->getData('name')]])."'>".$item[$this->getData('name')]."</a>";
            }
        }
        return $dataSource;
    }
}