<?php
namespace Riki\Sales\Plugin\Backend\Block\Widget;

use Riki\Sales\Model\Config\DeliveryOrderType;

class Context
{

    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    protected $_rikiSalesAdminHelper;

    public function __construct(
        \Magento\Framework\UrlInterface $urlBuilder,
        \Riki\Sales\Helper\Admin $adminHelper
    ){
        $this->_rikiSalesAdminHelper = $adminHelper;
        $this->_urlBuilder = $urlBuilder;
    }

    public function afterGetButtonList(
        \Magento\Backend\Block\Widget\Context $subject,
        $buttonList
    )
    {
        if($subject->getRequest()->getFullActionName() == 'sales_order_create_index'){
            $buttonList->add(
                'change_address_type',
                [
                    'label' => $this->getChangeAddressTypeButtonLabel(),
                    'onclick' => 'setLocation(\'' . $this->getChangeAddressTypeUrl() . '\')',
                ]
            );
        }

        return $buttonList;
    }

    /**
     * @return string
     */
    public function getChangeAddressTypeUrl(){

        $type = DeliveryOrderType::MULTIPLE_ADDRESS;

        if($this->_rikiSalesAdminHelper->isMultipleShippingAddressCart())
            $type = DeliveryOrderType::SINGLE_ADDRESS;

        return $this->_urlBuilder->getUrl('riki_sales/*/changeAddressType', ['type'   =>  $type]);
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getChangeAddressTypeButtonLabel(){
        if($this->_rikiSalesAdminHelper->isMultipleShippingAddressCart())
            return __('Ship To Single Address');

        return __('Ship To Multiple Address');
    }
}
