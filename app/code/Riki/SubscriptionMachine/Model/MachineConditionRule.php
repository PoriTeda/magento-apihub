<?php

namespace Riki\SubscriptionMachine\Model;

/**
 * Class MachineConditionRule
 * @package Riki\SubscriptionMachine\Model
 */
class MachineConditionRule extends \Magento\Framework\Model\AbstractModel
{
    const MACHINE_CODE_NBA = 'NBA';
    const MACHINE_CODE_NDG = 'NDG';
    const MACHINE_CODE_SPT = 'SPT';
    const MACHINE_CODE_BLC = 'BLC';
    const MACHINE_CODE_NESPRESSO = 'Nespresso';
    const MACHINE_CODE_DUO = 'DUO';

    protected function _construct()
    {
        $this->_init('Riki\SubscriptionMachine\Model\ResourceModel\MachineConditionRule');
    }

    /**
     * Get machine code option array
     * @return array
     */
    public function getMachineCodeOptionArray()
    {
        return [
            self::MACHINE_CODE_NBA => self::MACHINE_CODE_NBA,
            self::MACHINE_CODE_NDG => self::MACHINE_CODE_NDG,
            self::MACHINE_CODE_SPT => self::MACHINE_CODE_SPT,
            self::MACHINE_CODE_BLC => self::MACHINE_CODE_BLC,
            self::MACHINE_CODE_NESPRESSO => self::MACHINE_CODE_NESPRESSO,
            self::MACHINE_CODE_DUO => self::MACHINE_CODE_DUO,
        ];
    }
}
