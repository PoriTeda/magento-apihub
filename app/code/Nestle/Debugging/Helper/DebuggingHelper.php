<?php


namespace Nestle\Debugging\Helper;


use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class DebuggingHelper
 * @package Nestle\Debugging\Helper
 */
class DebuggingHelper
{

    /**
     *
     */
    const ENABLE_LOGGING = false;

    /**
     * @var \Nestle\Debugging\Model\Logger
     */
    private $logger;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    private $resource;

    /**
     * @var bool
     */
    protected $isTableExisted = false;
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    private $_connection;

    /**
     * @var array
     */
    protected $_mess = [];
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    private $timezone;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    private $json;

    /**
     * DebuggingHelper constructor.
     * @param \Nestle\Debugging\Model\Logger $logger
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Serialize\Serializer\Json $json,
        \Nestle\Debugging\Model\Logger $logger
    )
    {
        $this->logger = $logger;
        $this->resource = $resource;
        $this->timezone = $timezone;
        $this->json = $json;
    }

    /**
     * @return false|string|string[]|null
     */
    protected function debugStringBacktrace()
    {
        ob_start();
        debug_print_backtrace();
        $trace = ob_get_contents();
        ob_end_clean();

        // Remove first item from backtrace as it's this function which
        // is redundant.
        $trace = preg_replace('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);

        // Renumber backtrace items.
        $trace = preg_replace('/^#(\d+)/me', '\'#\' . ($1 - 1)', $trace);

        return $trace;
    }

    /**
     * @return $this
     */
    public function logServerIp()
    {
        if (!self::ENABLE_LOGGING) {
            return $this;
        }

        if (isset($_SERVER['SERVER_ADDR'])) {
            $this->addMessage($_SERVER['SERVER_ADDR']);
        }

        return $this;
    }

    /**
     * @param $mess
     * @return DebuggingHelper
     */
    public function log($mess)
    {
        return $this->addMessage($mess);
    }

    /**
     * @return string
     */
    protected function generateCallTrace($asArray = true)
    {
        $e = new \Exception();
        $trace = explode("\n", $e->getTraceAsString());
        // reverse array to make steps line up chronologically
        $trace = array_reverse($trace);
        array_shift($trace); // remove {main}
        array_pop($trace); // remove call to this method
        $length = count($trace);
        $result = array();

        for ($i = 0; $i < $length; $i++) {
            $result[] = ($i + 1) . ')' . substr($trace[$i], strpos($trace[$i], ' ')); // replace '#someNum' with '$i)', set the right ordering
        }

        if ($asArray) {
            return $result;
        } else {
            return "\t" . implode("\n\t", $result);
        }
    }

    /**
     * @param $mess
     * @return $this
     */
    public function addMessage($mess)
    {
//        $this->logger->debug($mess);
        if (!self::ENABLE_LOGGING) {
            return $this;
        }
        $this->_mess[] = $mess;

        return $this;
    }

    /**
     * @param string $type
     * @return $this
     * @throws \Zend_Db_Exception
     */
    public function save($type = "default")
    {

        if (!self::ENABLE_LOGGING) {
            return $this;
        }

//        $this->processTable();
//        $this->getConnection()->insert($this->getLoggingTableName(), [
//            "log" => $this->json->serialize($this->_mess),
//            "type" => $type,
//            "created_at" => $this->timezone->date(null, null, false)->format('Y-m-d H:i:s')
//        ]);

        $this->_mess = [];

        return $this;
    }

    /**
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected function getConnection()
    {
        if (is_null($this->_connection)) {
            $this->_connection = $this->resource->getConnection();
        }
        return $this->_connection;
    }

    /**
     * @param $object
     * @return $this
     */
    public function logClass($object)
    {
        if (!self::ENABLE_LOGGING) {
            return $this;
        }

        $this->addMessage("Class: " . get_class($object));
        return $this;
    }

    /**
     * @param $object
     * @return DebuggingHelper
     */
    public function inClass($object)
    {
        return $this->logClass($object);
    }

    /**
     *
     */
    public function logBacktrace()
    {
        if (!self::ENABLE_LOGGING) {
            return $this;
        }

        $this->addMessage($this->generateCallTrace());

        return $this;
    }

    /**
     * @return $this
     * @throws \Zend_Db_Exception
     */
    protected function processTable()
    {
        if ($this->isTableExisted || $this->resource->getConnection()->isTableExists($this->getLoggingTableName())) {
            $this->isTableExisted = true;
        } else {
            $this->buildTable();
            $this->isTableExisted = true;
        }

        return $this;
    }

    /**
     * @return $this
     * @throws \Zend_Db_Exception
     */
    protected function buildTable()
    {
        $table = $this->getConnection()->newTable($this->getLoggingTableName());
        $table
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true, 'unsigned' => true,],
                'Product id'
            )
            ->addColumn(
                'type',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true, 'unsigned' => true,],
                'type'
            )->addColumn(
                'log',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['nullable' => true, 'unsigned' => true,],
                'Log'
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                'Creation Time'
            )
            ->addIndex(
                $this->resource->getIdxName($this->getLoggingTableName(), ['id']),
                ['id']
            )
            ->addIndex(
                'FTI_FULLTEXT_DATA_INDEX',
                ['log'],
                ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
            );

        $this->getConnection()->createTable($table);

        return $this;
    }

    /**
     * @return string
     */
    protected function getLoggingTableName()
    {
        return $this->resource->getTableName("nestle_debugging_logging");
    }
}
