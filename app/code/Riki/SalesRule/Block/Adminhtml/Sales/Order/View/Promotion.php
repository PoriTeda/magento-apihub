<?php
namespace Riki\SalesRule\Block\Adminhtml\Sales\Order\View;

class Promotion extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /**
     * @var
     */
    protected $_orderSalesRuleResource;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param \Riki\SalesRule\Model\ResourceModel\OrderSalesRule $orderSalesRuleResource
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Riki\SalesRule\Model\ResourceModel\OrderSalesRule $orderSalesRuleResource,
        array $data = []
    ){

        $this->_orderSalesRuleResource = $orderSalesRuleResource;

        parent::__construct(
            $context,
            $registry,
            $adminHelper,
            $data
        );
    }

    /**
     * @return \Magento\Framework\DataObject[]|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getPromotionListByCurrentOrder(){
        $order = $this->getOrder();

        return $this->_orderSalesRuleResource->getRulesByOrder($order);
    }
}
