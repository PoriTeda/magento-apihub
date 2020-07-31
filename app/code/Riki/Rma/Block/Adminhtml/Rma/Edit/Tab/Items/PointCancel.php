<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items;

class PointCancel extends \Magento\Backend\Block\Template
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
     * PointCancel constructor.
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
     * Get total cancel point
     *
     * @return float|int|mixed
     */
    public function getTotalCancelPoint()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        return intval($this->amountHelper->getEarnedPoint($this->getRma()));
    }

    /**
     * Get total cancel point adj
     *
     * @return int|mixed
     */
    public function getTotalCancelPointAdj()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        if (intval($this->getRma()->getData('total_cancel_point_adj'))) {
            return intval($this->getRma()->getData('total_cancel_point_adj'));
        }

        return 0;
    }

    /**
     * Get points to cancel
     *
     * @return float|int
     */
    public function getPointToCancel()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        return intval($this->amountHelper->getPointsToCancel($this->getRma()));
    }

    /**
     * Get retractable points
     *
     * @return float|int
     */
    public function getRetractablePoint()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        return intval($this->amountHelper->getRetractablePoints($this->getRma()));
    }

    /**
     * Get not retractable points
     *
     * @return float|int
     */
    public function getNotRetractablePoint()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        return intval($this->amountHelper->getNotRetractablePoints($this->getRma()));
    }
}