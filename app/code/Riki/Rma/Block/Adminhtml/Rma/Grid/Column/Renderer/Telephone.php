<?php
namespace Riki\Rma\Block\Adminhtml\Rma\Grid\Column\Renderer;

use Riki\Customer\Model\Address\AddressType;

class Telephone extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\CustomerRepository
     */
    protected $customerRepository;

    /**
     * Telephone constructor.
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Customer\Model\ResourceModel\CustomerRepository $customerRepository,
        array $data = []
    ) {
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $customerId = $row->getCustomerId();
        if ($customerId) {
            try {
                $customer = $this->customerRepository->getById($customerId);
                $customerAddress = $customer->getAddresses();
                /** @var \Magento\Customer\Model\Address $address */
                foreach ($customerAddress as $address) {
                    $rikiAddressTypeAttr = $address->getCustomAttribute('riki_type_address');
                    if ($rikiAddressTypeAttr->getValue() == AddressType::HOME) {
                        return $address->getTelephone();
                    }
                }
            } catch (\Exception $e) {
                return '';
            }
        }
        return '';
    }
}
