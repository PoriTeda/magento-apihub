<?php
namespace Riki\Sales\Block\Adminhtml\Order\Creditmemo;

use Magento\Sales\Model\Order\Creditmemo;

/**
 * Adminhtml order creditmemo totals block
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Totals extends \Magento\Sales\Block\Adminhtml\Totals
{
    /**
     * Creditmemo
     *
     * @var Creditmemo|null
     */
    protected $creditmemo;

    /**
     * Retrieve creditmemo model instance
     *
     * @return Creditmemo
     */
    public function getCreditmemo()
    {
        if ($this->creditmemo === null) {
            if ($this->hasData('creditmemo')) {
                $this->creditmemo = $this->_getData('creditmemo');
            } elseif ($this->_coreRegistry->registry('currentcreditmemo')) {
                $this->creditmemo = $this->_coreRegistry->registry('currentcreditmemo');
            } elseif ($this->getParentBlock() && $this->getParentBlock()->getCreditmemo()) {
                $this->creditmemo = $this->getParentBlock()->getCreditmemo();
            }
        }
        return $this->creditmemo;
    }

    /**
     * Get source
     *
     * @return Creditmemo|null
     */
    public function getSource()
    {
        return $this->getCreditmemo();
    }

    /**
     * Initialize creditmemo totals array
     *
     * @return $this
     */
    protected function _initTotals()
    {
        parent::_initTotals();
        $this->addTotal(
            new \Magento\Framework\DataObject(
                [
                    'code' => 'return_shipping_fee_adjusted',
                    'value' => $this->getSource()->getData('return_shipping_fee_adjusted'),
                    'base_value' => $this->getSource()->getData('return_shipping_fee_adjusted'),
                    'label' => __('Final Refund Shipping Fee'),
                ]
            )
        );
        $this->addTotal(
            new \Magento\Framework\DataObject(
                [
                    'code' => 'return_payment_fee_adjusted',
                    'value' => $this->getSource()->getData('return_payment_fee_adjusted'),
                    'base_value' => $this->getSource()->getData('return_payment_fee_adjusted'),
                    'label' => __('Final Refund Payment Fee'),
                ]
            )
        );
        $this->addTotal(
            new \Magento\Framework\DataObject(
                [
                    'code' => 'return_point_not_retractable',
                    'value' => $this->getSource()->getData('return_point_not_retractable'),
                    'base_value' => $this->getSource()->getData('return_point_not_retractable'),
                    'label' => __('Return Point Not Retractable'),
                ]
            )
        );
        $this->addTotal(
            new \Magento\Framework\DataObject(
                [
                    'code' => 'total_return_amount_adj',
                    'value' => $this->getSource()->getData('total_return_amount_adj'),
                    'base_value' => $this->getSource()->getData('total_return_amount_adj'),
                    'label' => __('Total Refund Amount Adjustment'),
                ]
            )
        );
        $this->addTotal(
            new \Magento\Framework\DataObject(
                [
                    'code' => 'total_return_point_adjusted',
                    'value' => $this->getSource()->getData('total_return_point_adjusted'),
                    'base_value' => $this->getSource()->getData('total_return_point_adjusted'),
                    'label' => __('Final Point To Refund'),
                ]
            )
        );
        return $this;
    }
}
