<?php
namespace Riki\Customer\Ui\Component\EnquiryHeader\Listing\Grid\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
class OrderId extends Column
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $_orderFactory;

    /**
     * OrderId constructor.
     *
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $components = [],
        array $data = []
    ) {
        $this->_orderFactory = $orderFactory;
        parent::__construct($context,$uiComponentFactory, $components, $data);
    }

    /**
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $itemValue =  $item[$this->getData('name')];
                $orderid = $this->_orderFactory->create()->loadByIncrementId($itemValue)->getId();
                $item[$this->getData('name')] ="<a href='". $this->context->getUrl('sales/order/view',['order_id' => $orderid])."'>".$item[$this->getData('name')]."</a>";
            }
        }
        return $dataSource;
    }
}