<?php
namespace Riki\SubscriptionCourse\Block\Adminhtml\Course\Edit\Tab\Renderer;

class DelayPayment extends \Magento\Backend\Block\Template
{
    /**
     * @var \Riki\SubscriptionCourse\Model\DelayedPayment\ConfigProvider
     */
    protected $configProvider;

    /**
     * DelayPaymentJs constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Riki\SubscriptionCourse\Model\DelayedPayment\ConfigProvider $configProvider
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Riki\SubscriptionCourse\Model\DelayedPayment\ConfigProvider $configProvider,
        array $data = []
    ) {
        $this->configProvider = $configProvider;
        parent::__construct($context, $data);
    }

    /**
     *  Get list array allow Frequency of Delay Payment
     *
     * @return array
     */
    public function getAllowedFrequencies()
    {
        return json_encode($this->configProvider->getAllowedFrequencies());
    }
}
