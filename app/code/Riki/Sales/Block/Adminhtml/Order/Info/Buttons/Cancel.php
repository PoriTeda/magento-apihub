<?php
namespace Riki\Sales\Block\Adminhtml\Order\Info\Buttons;

/**
 * Class Cancel
 * @package Riki\Sales\Block\Order\Info\Buttons
 */
class Cancel extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{
    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Riki\Sales\Helper\Admin
     */
    protected $rikiAdminHelper;

    /**
     * Cancel constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Sales\Helper\Admin $adminHelper
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Riki\Sales\Helper\Admin $rikiAdminHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Riki\Sales\Helper\Admin $rikiAdminHelper,
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        $this->rikiAdminHelper = $rikiAdminHelper;

        parent::__construct($context, $registry, $adminHelper, $data);
    }

    /**
     * @return array
     */
    public function getReason()
    {
        return $this->rikiAdminHelper->getOrderCancelReasons();
    }

    /**
     * @return bool
     */
    public function isOfflineCustomer()
    {
        $order = $this->getOrder();

        try {
            $customer = $this->customerRepository->getById($order->getCustomerId());

            if ($customer->getCustomAttribute('offline_customer')
                && $customer->getCustomAttribute('offline_customer')->getValue()
            ) {
                return true;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }
}

