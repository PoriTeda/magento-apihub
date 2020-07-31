<?php


namespace Nestle\Migration\Preference\Magento\Framework\DB;


use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\DataConverter\DataConversionException;
use Magento\Framework\DB\DataConverter\DataConverterInterface;
use Magento\Framework\DB\FieldDataConversionException;
use Magento\Framework\DB\Query\Generator;
use Magento\Framework\DB\Select\QueryModifierInterface;
use Magento\Framework\DB\SelectFactory;
use Nestle\Migration\Model\DataMigration;

class FieldDataConverter extends \Magento\Framework\DB\FieldDataConverter
{
    /**
     * @var Generator
     */
    private $queryGenerator;

    /**
     * @var DataConverterInterface
     */
    private $dataConverter;

    /**
     * @var SelectFactory
     */
    private $selectFactory;

    /**
     * @var string|null
     */
    private $envBatchSize;
    /**
     * @var array
     */
    private $_conditions = [];

    /**
     * Constructor
     *
     * @param Generator $queryGenerator
     * @param DataConverterInterface $dataConverter
     * @param SelectFactory $selectFactory
     * @param string|null $envBatchSize
     */
    public function __construct(
        Generator $queryGenerator,
        DataConverterInterface $dataConverter,
        SelectFactory $selectFactory,
        $envBatchSize = null
    )
    {
        $this->queryGenerator = $queryGenerator;
        $this->dataConverter = $dataConverter;
        $this->selectFactory = $selectFactory;
        $this->envBatchSize = $envBatchSize;
        parent::__construct($queryGenerator, $dataConverter, $selectFactory, $envBatchSize);
    }

    /**
     * Convert table field data from one representation to another
     *
     * @param AdapterInterface $connection
     * @param string $table
     * @param string $identifier
     * @param string $field
     * @param QueryModifierInterface|null $queryModifier
     * @return void
     * @throws FieldDataConversionException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function convert(
        AdapterInterface $connection,
        $table,
        $identifier,
        $field,
        QueryModifierInterface $queryModifier = null
    )
    {
        if (!is_null(DataMigration::$OUTPUT)) {
            $select = $this->selectFactory->create($connection)
                                          ->from($table, [$identifier, $field])
                                          ->where($field . ' IS NOT NULL');
            if ($queryModifier) {
                $queryModifier->modify($select);
            }
            $iterator = $this->queryGenerator->generate($identifier, $select, $this->getBatchSize());
            foreach ($iterator as $selectByRange) {
                $rows = $connection->fetchPairs($selectByRange);
                $uniqueFieldDataArray = array_unique($rows);
                foreach ($uniqueFieldDataArray as $uniqueFieldData) {
                    $ids = array_keys($rows, $uniqueFieldData);
                    try {
                        $this->setCondition([$identifier . ' IN (?)' => $ids]);
                        $convertedValue = $this->dataConverter->convert($uniqueFieldData);
                        if ($uniqueFieldData === $convertedValue) {
                            // Skip for data rows that have been already converted
                            continue;
                        }
                        $bind = [$field => $convertedValue];
                        $where = [$identifier . ' IN (?)' => $ids];
                        $connection->update($table, $bind, $where);
                    } catch (DataConversionException $e) {
                        DataMigration::info("<info>fixing error data in table " . $table . "</info>");
                        $connection->delete($table, $this->getCondition());
                    }
                }
            }
        } else {
            parent::convert($connection, $table, $identifier, $field, $queryModifier);
        }

    }

    private function setCondition( $where)
    {
        $this->_conditions = $where;
    }

    private function getCondition()
    {
        return $this->_conditions;
    }

    /**
     * Get batch size from environment variable or default
     *
     * @return int
     */
    private function getBatchSize()
    {
        if (null !== $this->envBatchSize) {
            $batchSize = (int)$this->envBatchSize;
            if (bccomp($this->envBatchSize, PHP_INT_MAX, 0) === 1 || $batchSize < 1) {
                throw new \InvalidArgumentException(
                    'Invalid value for environment variable ' . self::BATCH_SIZE_VARIABLE_NAME . '. '
                    . 'Should be integer, >= 1 and < value of PHP_INT_MAX'
                );
            }
            return $batchSize;
        }
        return self::DEFAULT_BATCH_SIZE;
    }
}
