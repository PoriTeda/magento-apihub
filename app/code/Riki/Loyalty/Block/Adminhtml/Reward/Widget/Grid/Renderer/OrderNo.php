<?php

namespace Riki\Loyalty\Block\Adminhtml\Reward\Widget\Grid\Renderer;

class OrderNo extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
{
    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * OrderNo constructor.
     *
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Backend\Model\UrlInterface $urlBuilder
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Backend\Model\UrlInterface $urlBuilder,
        array $data = []
    )
    {
        parent::__construct($context, $data);
        $this->_urlBuilder = $urlBuilder;
    }

    /**
     * Link to order detail
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        if ($orderNo = $row->getData('order_no')) {
            $url = '#';
            if ($orderId = $row->getData('order_id')) {
                $url = $this->_urlBuilder->getUrl('sales/order/view', ['order_id' => $orderId]);
            }
            return "<a href='{$url}'>{$orderNo}</a>";
        }
        return '-';
    }

}