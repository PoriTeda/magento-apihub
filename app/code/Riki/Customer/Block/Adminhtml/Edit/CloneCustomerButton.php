<?php
namespace Riki\Customer\Block\Adminhtml\Edit;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class CloneCustomerButton
 * @package Riki\Customer\Block\Adminhtml\Edit
 */
class CloneCustomerButton extends \Magento\Customer\Block\Adminhtml\Edit\GenericButton implements ButtonProviderInterface
{
    /**
     * @var AccountManagementInterface
     */
    protected $customerAccountManagement;

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $_authorization;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param AccountManagementInterface $customerAccountManagement
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        AccountManagementInterface $customerAccountManagement
    )
    {
        parent::__construct($context, $registry);
        $this->customerAccountManagement = $customerAccountManagement;
        $this->_authorization = $context->getAuthorization();
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $customerId = $this->getCustomerId();

         if (
            !$customerId ||
            !$this->_authorization->isAllowed('Riki_Customer::edit') ||
            !$this->_authorization->isAllowed('Riki_Customer::new')
         ) {
             return false;
         }

        $url = $this->getUrl('customer/index/edit', ['copy_id' => $customerId]);
        $canModify = !$customerId || !$this->customerAccountManagement->isReadonly($this->getCustomerId());
        $data = [];
        if ($canModify) {
            $data = [
                'label' => __('Copy Customer'),
                'class' => 'save primary',
                'data_attribute' => [
                    'mage-init' => ['button' => ['event' => 'save']],
                    'form-role' => 'save',
                ],
                'sort_order' => 100,
                'on_click' => 'setLocation(\'' . $url . '\')',
            ];
        }
        return $data;
    }
}