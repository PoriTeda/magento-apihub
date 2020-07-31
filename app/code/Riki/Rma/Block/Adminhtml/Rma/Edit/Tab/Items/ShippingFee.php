<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items;

use Riki\Rma\Model\Config\Source\Rma\ReturnStatus;

class ShippingFee extends \Magento\Backend\Block\Template
{
    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $amountHelper;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * ShippingFee constructor.
     *
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Riki\Rma\Helper\Amount $amountHelper
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\Rma\Helper\Data $dataHelper,
        \Riki\Rma\Helper\Amount $amountHelper,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ){
        $this->dataHelper = $dataHelper;
        $this->amountHelper = $amountHelper;
        parent::__construct($context, $data);
    }

    /**
     * Get template
     *
     * @return string
     */
    public function getTemplate()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return false;
        }

        return parent::getTemplate();
    }

    /**
     * Getter
     *
     * @return \Magento\Rma\Model\Rma
     */
    public function getRma()
    {
        return $this->dataHelper->getCurrentRma();
    }


    /**
     * Get return shipping fee
     *
     * @return float|int|mixed
     */
    public function getReturnShippingFee()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        if ($this->getRma()->getReturnStatus() == ReturnStatus::COMPLETED) {
            return (int)$this->getRma()->getData('return_shipping_fee');
        }

        return intval($this->amountHelper->getReturnShippingAmount($this->getRma()));
    }

    /**
     * Get return shipping fee adj
     *
     * @return int|mixed
     */
    public function getReturnShippingFeeAdj()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        if (intval($this->getRma()->getData('return_shipping_fee_adj'))) {
            return intval($this->getRma()->getData('return_shipping_fee_adj'));
        }

        return 0;
    }
}