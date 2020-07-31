<?php
namespace Riki\Rma\Model\Config\Source;

class RefundPayment extends \Riki\Framework\Model\Source\AbstractOption
{
    /**
     * @var \Riki\Rma\Helper\Refund
     */
    protected $refundHelper;

    /**
     * RefundPayment constructor.
     * @param \Riki\Rma\Helper\Refund $refundHelper
     */
    public function __construct(
        \Riki\Rma\Helper\Refund $refundHelper
    )
    {
        $this->refundHelper = $refundHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $options = parent::toOptionArray();
        array_unshift($options, ['label' => __('Not allowed'), 'value' => '']);

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    public function prepare()
    {
        $options = [];
        foreach ($this->refundHelper->getEnableRefundMethods() as $id => $method) {
            $options[$id] = $method['title'];
        }

        return $options;
    }
}
