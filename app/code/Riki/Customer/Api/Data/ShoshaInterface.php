<?php
namespace Riki\Customer\Api\Data;

interface ShoshaInterface
{
    const SHOSHA_BUSINESS_CODE = 'shosha_business_code';
    const SHOSHA_CODE = 'shosha_code';
    const SHOSHA_CMP = 'shosha_cmp';
    const SHOSHA_CMP_KANA = 'shosha_cmp_kana';
    const SHOSHA_DEPT = 'shosha_dept';
    const SHOSHA_DEPT_KANA =  'shosha_dept_kana';
    const SHOSHA_IN_CHARGE = 'shosha_in_charge';
    const SHOSHA_IN_CHARGE_KANA = 'shosha_in_charge_kana';
    const SHOSHA_POSTCODE = 'shosha_postcode';
    const SHOSHA_ADDRESS1 = 'shosha_address1';
    const SHOSHA_ADDRESS1_KANA = 'shosha_address1_kana';
    const SHOSHA_ADDRESS2 = 'shosha_address2';
    const SHOSHA_ADDRESS2_KANA = 'shosha_address2_kana';
    const SHOSHA_PHONE = 'shosha_phone';
    const SHOSHA_FIRST_CODE = 'shosha_first_code';
    const SHOSHA_SECOND_CODE = 'shosha_second_code';
    const BLOCK_ORDERS = 'block_orders';
    const ORM_ROW_ID ='orm_rowid';
    const IS_BI_EXPORTED = 'is_bi_exported';
    const IS_CEDYNA_EXPORTED = 'is_cedyna_exported';
    const CEDYNA_COUNTER = 'cedyna_counter';
    const SHOSHA_COMMISSION = 'shosha_commission';

    /**
     * Get shosha_business_code
     *
     * @return string|null
     */
    public function getShoshaBusinessCode();

    /**
     * Set shosha_business_code
     *
     * @param $shoshaBusinessCode
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaBusinessCode($shoshaBusinessCode);

    /**
     * Get shosha_code
     *
     * @return int|null
     */
    public function getShoshaCode();

    /**
     * Set shosha_code
     *
     * @param $shoshaCode
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaCode($shoshaCode);

    /**
     * Get shosha_cmp
     *
     * @return string|null
     */
    public function getShoshaCmp();

    /**
     * Set shosha_cmp
     *
     * @param $shoshaCmp
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaCmp($shoshaCmp);

    /**
     * Get shosha_cmp_kana
     *
     * @return string|null
     */
    public function getShoshaCmpKana();

    /**
     * Set shosha_cmp_kana
     *
     * @param $shoshaCmpKana
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaCmpKana($shoshaCmpKana);

    /**
     * Get shosha_dept
     *
     * @return string|null
     */
    public function getShoshaDept();

    /**
     * Set shosha_dept
     *
     * @param $shoshaDept
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaDept($shoshaDept);

    /**
     * Get shosha_dept_kana
     *
     * @return string|null
     */
    public function getShoshaDeptKana();

    /**
     * Set shosha_dept_kana
     *
     * @param $shoshaDeptKana
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaDeptKana($shoshaDeptKana);

    /**
     * Get shosha_in_charge
     *
     * @return string|null
     */
    public function getShoshaInCharge();

    /**
     * Set shosha_in_charge
     *
     * @param $shoshaInCharge
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaInCharge($shoshaInCharge);

    /**
     * Get shosha_in_charge_kana
     *
     * @return string|null
     */
    public function getShoshaInChargeKana();

    /**
     * Set shosha_in_charge_kana
     *
     * @param $shoshaInChargeKana
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaInChargeKana($shoshaInChargeKana);

    /**
     * Get shosha_post_code
     *
     * @return string|null
     */
    public function getShoshaPostcode();

    /**
     * Set shosha_post_code
     *
     * @param $shoshaPostcode
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaPostcode($shoshaPostcode);

    /**
     * Get shosha_address1
     *
     * @return string|null
     */
    public function getShoshaAddress1();

    /**
     * Set shosha_address1
     *
     * @param $shoshaAddress1
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaAddress1($shoshaAddress1);

    /**
     * Get shosha_address1_kana
     *
     * @return string|null
     */
    public function getShoshaAddress1Kana();

    /**
     * Set shosha_address1_kana
     *
     * @param $shoshaAddress1Kana
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaAddress1Kana($shoshaAddress1Kana);

    /**
     * Get shosha_address2
     *
     * @return string|null
     */
    public function getShoshaAddress2();

    /**
     * Set shosha_address2
     *
     * @param $shoshaAddress2
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaAddress2($shoshaAddress2);

    /**
     * Get shosha_address2_kana
     *
     * @return string|null
     */
    public function getShoshaAddress2Kana();

    /**
     * Set shosha_address2_kana
     *
     * @param $shoshaAddress2Kana
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaAddress2Kana($shoshaAddress2Kana);

    /**
     * Get shosha_phone
     *
     * @return string|null
     */
    public function getShoshaPhone();

    /**
     * Set shosha_phone
     *
     * @param $shoshaPhone
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaPhone($shoshaPhone);

    /**
     * Get shosha_first_code
     *
     * @return string|int
     */
    public function getShoshaFirstCode();

    /**
     * Set shosha_first_code
     *
     * @param $shoshaFirstCode
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaFirstCode($shoshaFirstCode);

    /**
     * Get shosha_second_code
     *
     * @return string|null
     */
    public function getShoshaSecondCode();

    /**
     * Set shosha_second_code
     *
     * @param $shoshaSecondCode
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaSecondCode($shoshaSecondCode);

    /**
     * Get block_orders
     *
     * @return int|null
     */
    public function getBlockOrders();

    /**
     * Set block_orders
     *
     * @param $blockOrders
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setBlockOrders($blockOrders);

    /**
     * Get orm_rowid
     *
     * @return int|null
     */
    public function getOrmRowId();

    /**
     * Set orm_rowid
     *
     * @param $ormRowId
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setOrmRowId($ormRowId);

    /**
     * Get is_bi_exported
     *
     * @return int|null
     */
    public function getIsBiExported();

    /**
     * Set is_bi_exported
     *
     * @param $isBiExported
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setIsBiExported($isBiExported);

    /**
     * Get is_cedyna_exported
     *
     * @return int|null
     */
    public function getIsCedynaExported();

    /**
     * Set is_cedyna_exported
     *
     * @param $isCedynaExported
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setIsCedynaExported($isCedynaExported);

    /**
     * Get cedyna_counter
     *
     * @return int|null
     */
    public function getCedynaCounter();

    /**
     * Set cedyna_counter
     *
     * @param $cedynaCounter
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setCedynaCounter($cedynaCounter);

    /**
     * Get shosha_commission
     *
     * @return float|null
     */
    public function getShoshaCommission();

    /**
     * Set shosha_commission
     *
     * @param $shoshaCommission
     *
     * @return \Riki\Customer\Api\Data\ShoshaInterface
     */
    public function setShoshaCommission($shoshaCommission);
}