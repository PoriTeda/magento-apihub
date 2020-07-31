<?php
namespace Riki\SubscriptionMembership\Model\Customer\Attribute\Source;


class Membership extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    const CODE_1 = '1';
    const CODE_2 = '2';
    const CODE_3 = '3';
    const CODE_4 = '4';
    const CODE_5 = '5';
    const CODE_6 = '6';
    const CODE_7 = '7';
    const CODE_8 = '8';
    const CODE_9 = '9';
    const CODE_10 = '10';
    const CODE_11 = '11';
    const CODE_12 = '12';
    const CODE_13= '13';
    const CODE_14 = '14';
    const CODE_15 = '15';
    const CODE_16 = '16';
    const CODE_17 = '17';
    const CODE_18 = '18';
    const CODE_99 = '99';


    const AMB_MEMBERSHIP = '3';
    const CIS_MEMBERSHIP = '6';
    const CNC_MEMBERSHIP = '5';

    /**
     * @var OptionFactory
     */
    protected $optionFactory;

    /**
     * Get all options
     *
     * @return array
     */
    public function getAllOptions()
    {
        /* your Attribute options list*/
        $this->_options=[
            ['label'=>__('Off Line Members'), 'value'=>self::CODE_1],
            ['label'=>__('On Line Members'), 'value'=>self::CODE_2],
            ['label'=>__('Ambassador Members'), 'value'=>self::CODE_3],
            ['label'=>__('Invoice Members'), 'value'=>self::CODE_4],
            ['label'=>__('CNC Members'), 'value'=>self::CODE_5],
            ['label'=>__('CIS Members'), 'value'=>self::CODE_6],
            ['label'=>__('Milano Members'), 'value'=>self::CODE_7],
            ['label'=>__('Alegria Members'), 'value'=>self::CODE_8],
            ['label'=>__('Employee Members'), 'value'=>self::CODE_9],
            ['label'=>__('Sattelite Members'), 'value'=>self::CODE_10],
            ['label'=>__('Chocollatory Members'), 'value'=>self::CODE_11],
            ['label'=>__('Kitkat Members'), 'value'=>self::CODE_12],
            ['label'=>__('Wellness club cat Members'), 'value'=>self::CODE_13],
            ['label'=>__('Wellness club Members'), 'value'=>self::CODE_14],
            ['label'=>__('Wellness Ambassador Members'), 'value'=>self::CODE_15],
            ['label'=>__('Friend Ambassador Members'), 'value'=>self::CODE_16],
            ['label'=>__('Satellite Ambassador Members'), 'value'=>self::CODE_17],
            ['label' => __('NescafeStand Members'), 'value' => self::CODE_18]
        ];
        return $this->_options;
    }

    /**
     * @return array
     */
    public static function getOptionArray()
    {
        return [
            self::CODE_1 => __('Normal Members'),
            self::CODE_2 => __('Subscriber Members'),
            self::CODE_3 => __('Ambassador Members'),
            self::CODE_4 => __('CNC Members'),
            self::CODE_5 => __('CIS Members'),
            self::CODE_6=> __('MILANO Members'),
            self::CODE_7 => __('ALLEGRIA Members'),
            self::CODE_8 => __('Employee Members'),
            self::CODE_18 => __('NescafeStand Members'),
        ];
    }

    /**
     * Get a text for option value
     *
     * @param string|integer $value
     * @return string|bool
     */
    public function getOptionText($value)
    {
        foreach ($this->getAllOptions() as $option) {
            if ($option['value'] == $value) {
                return $option['label'];
            }
        }
        return false;
    }

    /**
     * Retrieve flat column definition
     *
     * @return array
     */
    public function getFlatColumns()
    {
        $attributeCode = $this->getAttribute()->getAttributeCode();
        return [
            $attributeCode => [
                'unsigned' => false,
                'default' => null,
                'extra' => null,
                'type' => Table::TYPE_INTEGER,
                'nullable' => true,
                'comment' => 'Custom Attribute Options  ' . $attributeCode . ' column',
            ],
        ];
    }

    /**
     * @return array
     */

    public function toOptionArray()
    {
        return $this->getAllOptions();
    }

    /**
     * @param $optionId
     * @return bool
     */
    public function getOptionValue($optionId)
    {
        $option = [
            self::CODE_1 => __('Off Line Members'),
            self::CODE_2 => __('On Line Members'),
            self::CODE_3 => __('Ambassador Members'),
            self::CODE_4 => __('Invoice Members'),
            self::CODE_5 => __('CNC Members'),
            self::CODE_6 => __('CIS Members'),
            self::CODE_7 => __('Milano Members'),
            self::CODE_8 => __('Alegria Members'),
            self::CODE_9 => __('Employee Members'),
            self::CODE_10 => __('Sattelite Members'),
            self::CODE_11 => __('Chocollatory Members'),
            self::CODE_12 => __('Kitkat Members'),
            self::CODE_13 => __('Wellness club cat Members'),
            self::CODE_14 => __('Wellness club Members'),
            self::CODE_15 =>__('Wellness Ambassador Members'),
            self::CODE_16 => __('Friend Ambassador Members'),
            self::CODE_17 =>__('Satellite Ambassador Members'),
            self::CODE_18 => __('NescafeStand Members'),
        ];

        if( !empty( $option[$optionId] ) ){
            return $option[$optionId];
        } else {
            return false;
        }

    }
}