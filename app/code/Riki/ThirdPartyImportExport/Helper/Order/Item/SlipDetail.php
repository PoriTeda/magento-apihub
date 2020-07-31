<?php
namespace Riki\ThirdPartyImportExport\Helper\Order\Item;

class SlipDetail extends \Riki\ThirdPartyImportExport\Helper\Order\Item\Transform
{
    /**
     * Get order of item
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        if (!$this->_loadedData['order']) {
            $this->_loadedData['order'] = $this->_subject->getOrder();
        }

        return $this->_loadedData['order'];
    }

    public function transform1()
    {
        $order = $this->getOrder();
        if (!$order) {
            return '';
        }

        return $order->getRealOrderId();
    }

    public function transform2()
    {
        return $this->_subject->getSku();
    }

    public function transform3()
    {
        return $this->_subject->getName();
    }

    public function transform4()
    {
        return $this->_subject->getQtyOrdered();
    }

    public function transform5()
    {
        return $this->_subject->getPrice();
    }

    public function transform6()
    {
        return $this->_subject->getData('tax_riki');
    }

    public function transform7()
    {
        return $this->_subject->getPrice() * $this->_subject->getQtyOrdered();
    }
}
