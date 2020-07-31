<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\General;

use Riki\Rma\Model\Config\Source\Rma\ReturnStatus;

class OrderTotal extends \Magento\Rma\Block\Adminhtml\Rma\Edit\Tab\General\AbstractGeneral
{
    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $helper;

    /**
     * OrderTotal constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Rma\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Riki\Rma\Helper\Data $helper,
        array $data = []
    ) {
        $this->helper = $helper;

        parent::__construct(
            $context,
            $registry,
            $data
        );
    }

    /**
     * Get total amount of order
     *
     * @return float
     */
    public function getOrderTotal()
    {
        return $this->getOrder()->formatPrice($this->getOrder()->getGrandTotal());
    }

    /**
     * Get total amount of return order
     *
     * @return string
     */
    public function getReturnsTotalOfOrder()
    {
        $order = $this->getOrder();

        return $order->formatPrice(
            $this->helper->getReturnsAmountTotalByOrder($order)
        );
    }

    /**
     * @return string
     */
    public function getTotalAmountOfCurrentReturn()
    {
        $returnStatus = $this->getRmaData('return_status');

        $amount = 0;

        if (in_array(
            $returnStatus,
            [
                ReturnStatus::APPROVED_BY_CC,
                ReturnStatus::REVIEWED_BY_CC,
                ReturnStatus::COMPLETED
            ]
        )) {
            $amount = $this->getRmaData('total_return_amount_adjusted');
        }

        return $this->getOrder()->formatPrice($amount);
    }
}
