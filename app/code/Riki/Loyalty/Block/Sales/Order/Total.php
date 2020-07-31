<?php

namespace Riki\Loyalty\Block\Sales\Order;

class Total extends \Magento\Framework\View\Element\Template
{
    /**
     * Reward resource model factory
     *
     * @var \Riki\Loyalty\Model\ResourceModel\RewardFactory
     */
    protected $_rewardData;

    protected $appState;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Riki\Loyalty\Model\ResourceModel\RewardFactory $rewardData
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Riki\Loyalty\Model\ResourceModel\RewardFactory $rewardData,
        array $data = []
    ) {
        $this->_rewardData = $rewardData;
        $this->appState = $context->getAppState();
        parent::__construct($context, $data);
    }

    /**
     * Get label cell tag properties
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getLabelProperties()
    {
        return $this->getParentBlock()->getLabelProperties();
    }

    /**
     * Get order store object
     *
     * @return \Magento\Sales\Model\Order
     * @codeCoverageIgnore
     */
    public function getOrder()
    {
        return $this->getParentBlock()->getOrder();
    }

    /**
     * Get totals source object
     *
     * @return \Magento\Sales\Model\Order
     * @codeCoverageIgnore
     */
    public function getSource()
    {
        return $this->getParentBlock()->getSource();
    }

    /**
     * Get value cell tag properties
     *
     * @return string
     * @codeCoverageIgnore
     */
    public function getValueProperties()
    {
        return $this->getParentBlock()->getValueProperties();
    }

    /**
     * Initialize reward points totals
     *
     * @return $this
     */
    public function initTotals()
    {
        $pointEarnArea = $this->getPointEarnArea();
        $pointEarnAfter = $this->getPointEarnAfter();
        $source = $this->getSource();
        $usedPointAmount = (double)$this->getOrder()->getUsedPointAmount();
        $paymentAmount = $source->getBaseGrandTotal() + $usedPointAmount;
        $this->getParentBlock()->addTotal(
            new \Magento\Framework\DataObject(
                [
                    'code' => 'grand_total_before_apply_point',
                    'strong' => false,
                    'label' => __('Total Payment (Incl. Tax)'),
                    'value' => $paymentAmount,
                    'area' => $this->getPaymentTotalArea()
                ]
            ), 'point'
        );

        $value = $source->getUsedPointAmount();
        $this->getParentBlock()->addTotalBefore(
            new \Magento\Framework\DataObject(
                [
                    'code' => 'point',
                    'strong' => false,
                    'label' => __('Shopping Point Use'),
                    'value' => $value
                ]
            ), 'grand_total_before_apply_point'
        );

        //show point earn at frontend
        if ( $this->appState->getAreaCode()== \Magento\Framework\App\Area::AREA_FRONTEND )
        {
            $earnedPoint = (int) $this->getOrder()->getData('bonus_point_amount');
            if (!$earnedPoint) {
                /** @var \Riki\Loyalty\Model\ResourceModel\Reward $resourceModel */
                $resourceModel = $this->_rewardData->create();
                $earnedPoint = $resourceModel->getTentative($this->getOrder()->getIncrementId());
            }
            if ($earnedPoint) {
                $dataObject = [
                    'code' => 'point_earn',
                    'strong' => true,
                    'label' => __('Earned Point'),
                    'value' => number_format($earnedPoint).__('point'),
                    'is_formated' => true,
                ];
                if ($pointEarnArea) {
                    $dataObject['area'] = $pointEarnArea;
                }
                $this->getParentBlock()->addTotal(
                    new \Magento\Framework\DataObject($dataObject), $pointEarnAfter
                );
            }
        }

        return $this;
    }
}
