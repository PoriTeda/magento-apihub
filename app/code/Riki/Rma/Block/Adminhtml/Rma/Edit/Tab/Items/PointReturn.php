<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items;

class PointReturn extends \Magento\Backend\Block\Template
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
     * PointReturn constructor.
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
     * Get customer balance
     *
     * @return float
     */
    public function getPointBalance()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        return intval($this->amountHelper->getPointsBalance($this->getRma()));
    }

    /**
     * Get total return point
     *
     * @return int|mixed
     *
     * @deprecated
     */
    public function getTotalReturnPoint()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        return intval(
            $this->amountHelper->getPointsToReturn(
                $this->getRma(),
                $this->getRma()->getData('return_shipping_fee_adj'),
                $this->getRma()->getData('return_payment_fee_adj')
            )
        );
    }

    /**
     * Get total return point adj
     *
     * @return int|mixed
     */
    public function getTotalReturnPointAdj()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        if (intval($this->getRma()->getData('total_return_point_adj'))) {
            return intval($this->getRma()->getData('total_return_point_adj'));
        }

        return 0;
    }
}