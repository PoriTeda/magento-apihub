<?php
namespace Riki\Sales\Block\Order\PrintOrder;
use Magento\Framework\View\Element\Template\Context;

/**
 * Class AddressName
 * @package Riki\Sales\Block\Order\PrintOrder
 */
class AddressName extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'Magento_Sales::order/info/print.phtml';

     /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * AddressName constructor.
     *
     * @param Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Get Address name data
     *
     * @return mixed
     */
    public function getDataAddressName()
    {
        return $this->coreRegistry->registry('name_info_customer_print');
    }

}