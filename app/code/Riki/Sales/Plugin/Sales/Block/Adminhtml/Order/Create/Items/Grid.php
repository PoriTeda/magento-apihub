<?php
namespace Riki\Sales\Plugin\Sales\Block\Adminhtml\Order\Create\Items;

class Grid
{
    /**
     * @var \Riki\Sales\Helper\Admin
     */
    protected $adminHelper;

    /**
     * Grid constructor.
     * @param \Riki\Sales\Helper\Admin $adminHelper
     */
    public function __construct(
        \Riki\Sales\Helper\Admin $adminHelper
    )
    {
        $this->adminHelper = $adminHelper;
    }

    /**
     * Not allow use custom price for free order
     *
     * @param \Magento\Sales\Block\Adminhtml\Order\Create\Items\Grid $subject
     * @param $result
     * @return array|bool
     */
    public function afterCanApplyCustomPrice(
        \Magento\Sales\Block\Adminhtml\Order\Create\Items\Grid $subject,
        $result
    )
    {
        if ($result && $this->adminHelper->isFreeOfChargeOrder()) {
            $result = false;
        }

        return $result;
    }
}