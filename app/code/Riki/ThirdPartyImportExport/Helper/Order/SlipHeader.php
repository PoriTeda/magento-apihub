<?php
namespace Riki\ThirdPartyImportExport\Helper\Order;

class SlipHeader extends \Riki\ThirdPartyImportExport\Helper\Order\Transform
{
    /**
     * Get order billing address
     *
     * @return \Magento\Sales\Model\Order\Address
     */
    public function getOrderBillingAddress()
    {
        if (!isset($this->_loadedData['billingAddress'])) {
            $address = $this->_subject->getBillingAddress();
            $this->_loadedData['billingAddress'] = $address;
        }

        return $this->_loadedData['billingAddress'];
    }

    public function transform1()
    {
        return $this->_subject->getData('increment_id');
    }

    public function transform2()
    {
        return $this->transform1();
    }

    public function transform3()
    {
        return $this->_subject->getCustomerId();
    }

    public function transform4()
    {
        $address = $this->getOrderBillingAddress();

        if (!$address) {
            return '';
        }

        return $address->getLastname() . ' ' . $address->getFirstname();
    }

    public function transform5()
    {
        $address = $this->getOrderBillingAddress();

        if (!$address) {
            return '';
        }

        return $address->getData('lastnamekana') . ' ' . $address->getData('firstnamekana');
    }

    public function transform6()
    {
        $address = $this->getOrderBillingAddress();

        if (!$address) {
            return '';
        }

        return $address->getPostCode();
    }

    public function transform7()
    {
        return '';
    }

    public function transform8()
    {
        $address = $this->getOrderBillingAddress();

        if (!$address) {
            return '';
        }

        return $address->getRegion() . ' ' . implode(', ', $address->getStreet());
    }

    public function transform9()
    {
        $address = $this->getOrderBillingAddress();
        if (!$address) {
            return '';
        }

        return $address->getTelephone();
    }

    public function transform10()
    {
        return $this->_subject->getBaseSubtotal() +  (float)$this->_subject->getData('gw_items_base_price');
    }

    public function transform11()
    {
        return ($this->_subject->getBaseSubtotalInclTax() - $this->_subject->getBaseSubtotal()) + (float)$this->_subject->getData('gw_items_base_tax_amount');
    }

    public function transform12()
    {
        return $this->_subject->getBaseGrandTotal() - (float)$this->_subject->getData('used_point_amount');
    }

    public function transform13()
    {
        return date('Y-m-d H:i:s', strtotime("+10 days"));
    }

    public function transform14()
    {
        return $this->_subject->getShippingAmount();
    }

    /**
     * Get column name for Header CSV
     * @return array
     */
    public function getHeaderColumns()
    {
        return
            [
                /*01*/ 'order.increment_id',
                /*02*/ 'order.increment_id',
                /*03*/ 'order.customer_consumer_db_id',
                /*04*/ '',
                /*05*/ '',
                /*06*/ 'order.billing_address_postcode',
                /*07*/ '',
                /*08*/ '',
                /*09*/ 'order.billing_address_telephone',
                /*10*/ '',
                /*11*/ '',
                /*12*/ 'order.grand_total',
                /*13*/ '',
                /*14*/ 'order.shipping_amount'
            ];
    }
}
