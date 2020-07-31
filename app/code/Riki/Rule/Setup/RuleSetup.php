<?php

namespace Riki\Rule\Setup;

class RuleSetup
{
    protected $_setup;

    protected $_timeColumns = [
        'from_time' => [
            'type' => 'time',
            'nullable' => true,
            'comment' => 'From time'
        ],
        'to_time' => [
            'type' => 'time',
            'nullable' => true,
            'comment' => 'To time'
        ]
    ];

    /**
     * RuleSetup constructor.
     *
     * @param \Magento\Framework\Setup\ModuleDataSetupInterface $setup
     */
    public function __construct(
        \Magento\Framework\Setup\ModuleDataSetupInterface $setup
    )
    {
        $this->_setup = $setup;
    }

    /**
     * Add time columns to rule tables
     *
     * @param $table
     */
    public function addTimeColumns($table)
    {
        $connection = $this->_setup->getConnection();
        foreach ($this->_timeColumns as $columnName => $opts) {
            $connection->addColumn($table, $columnName, $opts);
        }
    }
}