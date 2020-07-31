<?php
namespace Riki\Framework\Helper\Transaction;

class Database
{
    /**
     * @var bool
     */
    protected $initialized = false;

    /**
     * @var array
     */
    protected $connectionNames = ['default', 'sales', 'checkout'];

    /**
     * @var array
     */
    protected $connectionInstances = [];

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * Database constructor.
     *
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     */
    public function __construct(
        \Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Initialize
     *
     * @return void
     */
    public function initialize()
    {
        if ($this->initialized) {
            return;
        }

        foreach ($this->connectionNames as $connectionName) {
            $connection = $this->resourceConnection->getConnection($connectionName);
            $hashKey = spl_object_hash($connection);
            if (isset($this->connectionInstances[$hashKey])) {
                continue;
            }
            $this->connectionInstances[$hashKey] = $connection;
        }

        $this->initialized = true;
    }

    /**
     * Begin transaction
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->initialize();
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connectionInstance */
        foreach ($this->connectionInstances as $k => $connectionInstance) {
            $connectionInstance->beginTransaction();
        }
    }

    /**
     * Commit changes
     *
     * @return void
     */
    public function commit()
    {
        $this->initialize();
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connectionInstance */
        foreach ($this->connectionInstances as $connectionInstance) {
            $connectionInstance->commit();
        }
    }

    /**
     * Rollback changes
     *
     * @return void
     */
    public function rollback()
    {
        $this->initialize();
        /** @var \Magento\Framework\DB\Adapter\AdapterInterface $connectionInstance */
        foreach ($this->connectionInstances as $connectionInstance) {
            $connectionInstance->rollBack();
        }
    }
}
