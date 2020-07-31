<?php

namespace Bluecom\PaymentFee\Model\ResourceModel;

class PaymentFee extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('payment_fee', 'entity_id');
    }

    /**
     * Load payment fee by code
     *
     * @param \Bluecom\PaymentFee\Model\PaymentFee $object      object
     * @param string                               $paymentCode payment code
     *
     * @return bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByCode(\Bluecom\PaymentFee\Model\PaymentFee $object, $paymentCode)
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->getMainTable()
        )->where(
            'payment_code = :payment_code'
        );
        $data = $connection->fetchRow($select, [':payment_code' => $paymentCode]);

        if (!$data) {
            return false;
        }

        $object->setData($data);

        $this->_afterLoad($object);
        return true;
    }

    /**
     * Get all payment fee codes
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAllCodes()
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->getMainTable(),
            ['payment_code', 'fixed_amount']
        )->where(
            'active = 1 AND fixed_amount > 0'
        );
        $data = $connection->fetchPairs($select);
        return $data;
    }

    /**
     * Get key codes
     *
     * @param array $data data key codes
     *
     * @return array
     */
    public function getKeyCodes($data)
    {
        $result = array_keys($data);
        return $result;
    }

    /**
     * Load payment code
     *
     * @param string $paymentCode payment code
     *
     * @return array|bool
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadPaymentCode($paymentCode)
    {
        $connection = $this->getConnection();

        $select = $connection->select()->from(
            $this->getMainTable()
        )->where(
            'payment_code = :payment_code'
        );
        $data = $connection->fetchRow($select, [':payment_code' => $paymentCode]);

        if (!$data) {
            return false;
        }

        return $data;
    }
}