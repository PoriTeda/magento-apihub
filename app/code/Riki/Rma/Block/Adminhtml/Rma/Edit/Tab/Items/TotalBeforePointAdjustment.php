<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Edit\Tab\Items;

use Magento\Rma\Model\Rma\Source\Status;

class TotalBeforePointAdjustment extends \Magento\Backend\Block\Template
{
    const NORMAL_RMA = 1;

    /**
     * @var \Riki\Rma\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Rma\Helper\Amount
     */
    protected $amountHelper;

    /**
     * @var \Riki\Loyalty\Model\RewardManagement
     */
    protected $rewardManagement;

    /**
     * TotalBefore constructor.
     * @param \Riki\Rma\Helper\Data $dataHelper
     * @param \Riki\Rma\Helper\Amount $amountHelper
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Riki\Rma\Helper\Data $dataHelper,
        \Riki\Rma\Helper\Amount $amountHelper,
        \Magento\Backend\Block\Template\Context $context,
        \Riki\Loyalty\Model\RewardManagement $rewardManagement,
        array $data = []
    ) {
        $this->dataHelper = $dataHelper;
        $this->amountHelper = $amountHelper;
        $this->rewardManagement = $rewardManagement;
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
     * @return \Riki\Rma\Model\Rma
     */
    public function getRma()
    {
        return $this->dataHelper->getCurrentRma();
    }

    /**
     * Get point not retractable
     *
     * @return float|int
     */
    public function getNotRetractablePoint()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        return $this->amountHelper->getNotRetractablePoints($this->getRma());
    }

    /**
     * Check COD/Np-Atobarai & Shipment Reject
     *
     * @return int
     */
    public function isCodAndNpAtobaraiShipmentRejected()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        return $this->dataHelper->isCodAndNpAtobaraiShipmentRejected($this->getRma());
    }

    /**
     * Get order used point
     *
     * @return int
     */
    public function getReturnablePoint()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        $order = $this->dataHelper->getRmaOrder($this->getRma());
        if (!$order instanceof \Magento\Sales\Model\Order) {
            return 0;
        }

        return intval($order->getUsedPoint() - $this->_getReturnedPoint());
    }

    /**
     * Get order used point amount (point converted to order currency)
     *
     * @return int
     */
    public function getReturnablePointAmount()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        $order = $this->dataHelper->getRmaOrder($this->getRma());
        if (!$order instanceof \Magento\Sales\Model\Order) {
            return 0;
        }

        if (in_array($this->getRma()->getStatus(), [
            Status::STATE_CLOSED,
            Status::STATE_PROCESSED_CLOSED
        ])) {
            return intval($this->getRma()->getData('returnable_point_amount'));
        }

        $returnedPointAmount = $this->rewardManagement->convertPointToAmount($this->_getReturnedPoint());
        $capturedPointAmount = $this->rewardManagement->convertPointToAmount(
            $this->amountHelper->getCapturedPoint($this->getRma())
        );

        return max(0, intval($order->getUsedPointAmount() - ($returnedPointAmount + $capturedPointAmount)));
    }

    /**
     * Get used point amount which were returned to customer already
     *
     * @return mixed
     */
    protected function _getReturnedPoint()
    {
        if (!$this->hasData('returned_point')) {
            $this->setData('returned_point', $this->amountHelper->getReturnedPoint($this->getRma()));
        }
        return $this->getData('returned_point');
    }

    /**
     * Get shipment shopping point
     *
     * @return int
     */
    public function getShipmentShoppingPoint()
    {
        if (!$this->getRma() instanceof \Magento\Rma\Model\Rma) {
            return 0;
        }

        $shipment = $this->dataHelper->getRmaShipment($this->getRma());
        if (!$shipment instanceof \Magento\Sales\Model\Order\Shipment) {
            return 0;
        }

        return intval($shipment->getShoppingPointAmount());
    }

    /**
     * @return int
     */
    public function getReturnGoodsType()
    {
        return self::NORMAL_RMA;
    }
}