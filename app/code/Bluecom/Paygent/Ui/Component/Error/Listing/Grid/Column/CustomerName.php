<?php
namespace Bluecom\Paygent\Ui\Component\Error\Listing\Grid\Column;

use Magento\Ui\Component\Listing\Columns\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class CustomerName extends Column
{

    protected $_customerRepositoryInterface;

    public  function  __construct(
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    )
    {
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

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
        foreach ($dataSource['data']['items'] as $key=> &$item) {
            if(isset($item['customer_firstname']) && $item['customer_firstname'] !=''){
               $item['customer_firstname'] = $item['customer_firstname'] . ' '. $item['customer_lastname'];
            }
        }
        return $dataSource;
    }
}