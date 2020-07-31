<?php
namespace Riki\Customer\Model;

class Shosha extends \Magento\Framework\Model\AbstractModel implements \Riki\Customer\Api\Data\ShoshaInterface
{
    const BLOCKORDERS_YES = 1;
    const BLOCKORDERS_NO = 0;

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Riki\Customer\Model\ResourceModel\Shosha');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getShoshaBusinessCode()
    {
        return $this->getData(self::SHOSHA_BUSINESS_CODE);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaBusinessCode
     *
     * @return $this
     */
    public function setShoshaBusinessCode($shoshaBusinessCode)
    {
        return $this->setData(self::SHOSHA_BUSINESS_CODE, $shoshaBusinessCode);
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getShoshaCode()
    {
        return $this->getData(self::SHOSHA_CODE);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaCode
     *
     * @return $this
     */
    public function setShoshaCode($shoshaCode)
    {
        return $this->setData(self::SHOSHA_CODE, $shoshaCode);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getShoshaCmp()
    {
        return $this->getData(self::SHOSHA_CMP);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaCmp
     *
     * @return $this
     */
    public function setShoshaCmp($shoshaCmp)
    {
        return $this->setData(self::SHOSHA_CMP, $shoshaCmp);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getShoshaCmpKana()
    {
        return $this->getData(self::SHOSHA_CMP_KANA);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaCmpKana
     *
     * @return $this
     */
    public function setShoshaCmpKana($shoshaCmpKana)
    {
        return $this->setData(self::SHOSHA_CMP_KANA, $shoshaCmpKana);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getShoshaDept()
    {
        return $this->getData(self::SHOSHA_DEPT);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaDept
     *
     * @return $this
     */
    public function setShoshaDept($shoshaDept)
    {
        return $this->setData(self::SHOSHA_DEPT);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getShoshaDeptKana()
    {
        return $this->getData(self::SHOSHA_DEPT_KANA);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaDeptKana
     *
     * @return $this
     */
    public function setShoshaDeptKana($shoshaDeptKana)
    {
        return $this->setData(self::SHOSHA_DEPT_KANA, $shoshaDeptKana);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getShoshaInCharge()
    {
        return $this->getData(self::SHOSHA_IN_CHARGE);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaInCharge
     *
     * @return $this
     */
    public function setShoshaInCharge($shoshaInCharge)
    {
        return $this->setData(self::SHOSHA_IN_CHARGE, $shoshaInCharge);
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getShoshaInChargeKana()
    {
        return $this->getData(self::SHOSHA_IN_CHARGE_KANA);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaInChargeKana
     *
     * @return $this
     */
    public function setShoshaInChargeKana($shoshaInChargeKana)
    {
        return $this->setData(self::SHOSHA_IN_CHARGE_KANA, $shoshaInChargeKana);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getShoshaPostcode()
    {
        return $this->getData(self::SHOSHA_POSTCODE);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaPostcode
     *
     * @return $this
     */
    public function setShoshaPostcode($shoshaPostcode)
    {
        return $this->setData(self::SHOSHA_POSTCODE, $shoshaPostcode);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getShoshaAddress1()
    {
        return $this->getData(self::SHOSHA_ADDRESS1);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaAddress1
     *
     * @return $this
     */
    public function setShoshaAddress1($shoshaAddress1)
    {
        return $this->setData(self::SHOSHA_ADDRESS1, $shoshaAddress1);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getShoshaAddress1Kana()
    {
        return $this->getData(self::SHOSHA_ADDRESS1_KANA);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaAddress1Kana
     *
     * @return $this
     */
    public function setShoshaAddress1Kana($shoshaAddress1Kana)
    {
        return $this->setData(self::SHOSHA_ADDRESS1_KANA, $shoshaAddress1Kana);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getShoshaAddress2()
    {
        return $this->getData(self::SHOSHA_ADDRESS2);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaAddress2
     *
     * @return $this
     */
    public function setShoshaAddress2($shoshaAddress2)
    {
        return $this->setData(self::SHOSHA_ADDRESS2, $shoshaAddress2);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getShoshaAddress2Kana()
    {
        return $this->getData(self::SHOSHA_ADDRESS2_KANA);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaAddress2Kana
     *
     * @return $this
     */
    public function setShoshaAddress2Kana($shoshaAddress2Kana)
    {
        return $this->setData(self::SHOSHA_ADDRESS2_KANA, $shoshaAddress2Kana);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getShoshaPhone()
    {
        return $this->getData(self::SHOSHA_PHONE);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaPhone
     *
     * @return $this
     */
    public function setShoshaPhone($shoshaPhone)
    {
        return $this->setData(self::SHOSHA_PHONE, $shoshaPhone);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getShoshaFirstCode()
    {
        return $this->getData(self::SHOSHA_FIRST_CODE);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaFirstCode
     *
     * @return $this
     */
    public function setShoshaFirstCode($shoshaFirstCode)
    {
        return $this->setData(self::SHOSHA_FIRST_CODE, $shoshaFirstCode);
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getShoshaSecondCode()
    {
        return $this->getData(self::SHOSHA_SECOND_CODE);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaSecondCode
     *
     * @return $this
     */
    public function setShoshaSecondCode($shoshaSecondCode)
    {
        return $this->setData(self::SHOSHA_SECOND_CODE, $shoshaSecondCode);
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getBlockOrders()
    {
        return $this->getData(self::BLOCK_ORDERS);
    }

    /**
     * {@inheritdoc}
     *
     * @param $blockOrders
     *
     * @return $this
     */
    public function setBlockOrders($blockOrders)
    {
        return $this->setData(self::BLOCK_ORDERS, $blockOrders);
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getOrmRowId()
    {
        return $this->getData(self::ORM_ROW_ID);
    }

    /**
     * {@inheritdoc}
     *
     * @param $ormRowId
     *
     * @return $this
     */
    public function setOrmRowId($ormRowId)
    {
        return $this->setData(self::ORM_ROW_ID, $ormRowId);
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getIsBiExported()
    {
        return $this->getData(self::IS_BI_EXPORTED);
    }

    /**
     * {@inheritdoc}
     *
     * @param $isBiExported
     *
     * @return $this
     */
    public function setIsBiExported($isBiExported)
    {
        return $this->setData(self::IS_BI_EXPORTED, $isBiExported);
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getIsCedynaExported()
    {
        return $this->getData(self::IS_CEDYNA_EXPORTED);
    }

    /**
     * {@inheritdoc}
     *
     * @param $isCedynaExported
     *
     * @return $this
     */
    public function setIsCedynaExported($isCedynaExported)
    {
        return $this->setData(self::IS_CEDYNA_EXPORTED, $isCedynaExported);
    }

    /**
     * {@inheritdoc}
     *
     * @return int|null
     */
    public function getCedynaCounter()
    {
        return $this->getData(self::CEDYNA_COUNTER);
    }

    /**
     * {@inheritdoc}
     *
     * @param $cedynaCounter
     *
     * @return $this
     */
    public function setCedynaCounter($cedynaCounter)
    {
        return $this->setData(self::CEDYNA_COUNTER, $cedynaCounter);
    }

    /**
     * {@inheritdoc}
     *
     * @return float|null
     */
    public function getShoshaCommission()
    {
        return $this->getData(self::SHOSHA_COMMISSION);
    }

    /**
     * {@inheritdoc}
     *
     * @param $shoshaCommission
     *
     * @return $this
     */
    public function setShoshaCommission($shoshaCommission)
    {
        return $this->setData(self::SHOSHA_COMMISSION, $shoshaCommission);
    }


}