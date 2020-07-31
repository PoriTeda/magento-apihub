<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items;

class PaymentFee extends \Magento\Backend\Block\Template
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
     * PaymentFee constructor.
     *
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Riki\Rma\Helper\Amount $amountHelper
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\Rma\Helper\Data $dataHelper,
        \Magento\Framework\Registry $registry,
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
     * Get customer balance
     *
     * @return float
     */
    public function getPointBalance()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        return $this->amountHelper->getPointsBalance($this->getRma());
    }

    /**
     * Get return payment fee
     *
     * @return int|mixed
     */
    public function getReturnPaymentFee()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        return intval($this->amountHelper->getReturnPaymentFee($this->getRma()));
    }

    /**
     * Get return payment fee adj
     *
     * @return int|mixed
     */
    public function getReturnPaymentFeeAdj()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        if (intval($this->getRma()->getData('return_payment_fee_adj'))) {
            return intval($this->getRma()->getData('return_payment_fee_adj'));
        }

        return 0;
    }
}