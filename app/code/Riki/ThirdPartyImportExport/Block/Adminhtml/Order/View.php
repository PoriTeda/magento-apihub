<?php
namespace Riki\ThirdPartyImportExport\Block\Adminhtml\Order;

use Magento\Framework\App\Response\RedirectInterface;

class View extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * @var string
     */
    protected $_blockGroup = 'Riki_ThirdPartyImportExport';
    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;
    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;
    protected $_redirect;

    /**
     * View constructor.
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\Block\Widget\Context $context,
        RedirectInterface $redirectInterface,
        array $data = []
    )
    {
        $this->_redirect = $redirectInterface;
        $this->_customerFactory = $customerFactory;
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * Initialize
     */
    protected function _construct()
    {
        $this->_objectId = 'order_no';
        $this->_controller = 'adminhtml_order';
        $this->_mode = 'view';

        parent::_construct();

        $this->removeButton('delete');
        $this->removeButton('reset');
        $this->removeButton('save');
    }

    /**
     * Get current order
     *
     * @return \Riki\ThirdPartyImportExport\Model\Order
     */
    public function getOrder()
    {
        return $this->_registry->registry('current_order');
    }

    /**
     * Get back url
     *
     * @return string
     */
    public function getBackUrl()
    {
        /** @var \Magento\Customer\Model\Customer $customer */
//        $customer = $this->getCustomer();
//
//        return $this->getUrl('customer/index/edit/', ['id' => $customer->getId()]);
        //return $this->_redirect->getRefererUrl();
        return $this->getUrl('thirdpartyimportexport/index/index/');
    }

    /**
     * Get customer
     * 
     * @return \Magento\Framework\DataObject
     */
    public function getCustomer()
    {
        $order = $this->getOrder();
        /** @var \Magento\Customer\Model\ResourceModel\Customer\Collection $collection */
        $collection = $this->_customerFactory->create()->getCollection()
            ->addFieldToFilter('consumer_db_id', ['eq' => $order->getCustomerCode()]);

        if (!$collection->getSize()) {
            return $this->_customerFactory->create();
        }

        return $collection->getFirstItem();
    }
}
