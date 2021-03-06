<?php


namespace Nestle\Migration\Model\DataMigration;


use Magento\Framework\DB\Ddl\Table;
use Nestle\Migration\Model\DataMigration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FixColumnType extends AbstractDataMigration
{
    const FIX_COLUMN = [
        [
            "resource"   => "default",
            "table"      => "catalogrule",
            "column"     => "from_time",
            "old_type"   => "time",
            "definition" => [
                "type" => Table::TYPE_DATETIME
            ]
        ],
        [
            "resource"   => "default",
            "table"      => "catalogrule",
            "column"     => "to_time",
            "old_type"   => "time",
            "definition" => [
                "type" => Table::TYPE_DATETIME
            ]
        ],
        [
            "resource"   => "default",
            "table"      => "salesrule",
            "column"     => "from_time",
            "old_type"   => "time",
            "definition" => [
                "type" => Table::TYPE_DATETIME
            ]
        ],
        [
            "resource"   => "default",
            "table"      => "salesrule",
            "column"     => "to_time",
            "old_type"   => "time",
            "definition" => [
                "type" => Table::TYPE_DATETIME
            ]
        ],
        [
            "resource"   => "default",
            "table"      => "subscription_profile",
            "column"     => "frequency_unit",
            "old_type"   => "enum",
            "definition" => [
                "type"   => Table::TYPE_TEXT,
                "length" => 10
            ]
        ],
        [
            "resource"   => "sales",
            "table"      => "subscription_course",
            "column"     => "duration_unit",
            "old_type"   => "enum",
            "definition" => [
                "type"   => Table::TYPE_TEXT,
                "length" => 10
            ]
        ],
        [
            "resource"   => "sales",
            "table"      => "subscription_frequency",
            "column"     => "frequency_unit",
            "old_type"   => "enum",
            "definition" => [
                "type"   => Table::TYPE_TEXT,
                "length" => 10
            ]
        ],
        [
            "resource"   => "sales",
            "table"      => "subscription_profile",
            "column"     => "frequency_unit",
            "old_type"   => "enum",
            "definition" => [
                "type"   => Table::TYPE_TEXT,
                "length" => 10
            ]
        ]
    ];

    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->fixColumnType($input, $output);
        $this->fixData();
        return parent::run($input, $output); // TODO: Change the autogenerated stub
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return $this
     */
    public function fixColumnType(InputInterface $input, OutputInterface $output)
    {
        foreach (self::FIX_COLUMN as $item) {
            $columns = $this->readColumns($item['table'], $item['resource']);
            if (isset($columns[$item["column"]]) && $columns[$item["column"]]["type"] == $item["old_type"]) {
                DataMigration::info("fixing column `" . $item["column"] . "` in table `" . $item['resource'] . "`.`" . $item['table'] . "` with unsupported type: `" . $item["old_type"] . "`");
                $adapter = $this->resourceConnection->getConnection($item['resource']);
                $adapter->changeColumn(
                    $adapter->getTableName($item["table"]),
                    $item["column"],
                    $item["column"],
                    $item["definition"]
                );
            }
        }

        return $this;
    }

    /**
     * Only run when local before change type to int
     */
    private function fixData()
    {
        $fixingColumnData = [
            [
                "resource"   => "default",
                "table"      => "subscription_profile",
                "column"     => "frequency_unit",
                "old_type"   => Table::TYPE_SMALLINT,
                "definition" => [
                    "type"   => Table::TYPE_TEXT,
                    "length" => 10
                ]
            ],
            [
                "resource"   => "sales",
                "table"      => "subscription_course",
                "column"     => "duration_unit",
                "old_type"   => Table::TYPE_SMALLINT,
                "definition" => [
                    "type"   => Table::TYPE_TEXT,
                    "length" => 10
                ]
            ],
            [
                "resource"   => "sales",
                "table"      => "subscription_frequency",
                "column"     => "frequency_unit",
                "old_type"   => Table::TYPE_SMALLINT,
                "definition" => [
                    "type"   => Table::TYPE_TEXT,
                    "length" => 10
                ]
            ],
            [
                "resource"   => "sales",
                "table"      => "subscription_profile",
                "column"     => "frequency_unit",
                "old_type"   => Table::TYPE_SMALLINT,
                "definition" => [
                    "type"   => Table::TYPE_TEXT,
                    "length" => 10
                ]
            ]
        ];

        foreach ($fixingColumnData as $item) {
            $columns = $this->readColumns($item['table'], $item['resource']);
            if (isset($columns[$item["column"]]) && $columns[$item["column"]]["type"] == $item["old_type"]) {
                DataMigration::info("fixing column `" . $item["column"] . "` in table `" . $item['resource'] . "`.`" . $item['table'] . "` with unsupported type: `" . $item["old_type"] . "`");
                $adapter = $this->resourceConnection->getConnection($item['resource']);
                $adapter->changeColumn(
                    $adapter->getTableName($item["table"]),
                    $item["column"],
                    $item["column"],
                    $item["definition"]
                );

                $adapter->update($adapter->getTableName($item["table"]), [
                    $item["column"] => "week"
                ], $item["column"] . ' = 1');

                $adapter->update($adapter->getTableName($item["table"]), [
                    $item["column"] => "month"
                ], $item["column"] . ' = 2');
            }
        }
    }
}
