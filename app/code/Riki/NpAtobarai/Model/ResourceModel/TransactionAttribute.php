<?php

namespace Riki\NpAtobarai\Model\ResourceModel;

use Exception;
use Magento\Framework\App\ResourceConnection as AppResource;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Riki\NpAtobarai\Model\Transaction;

class TransactionAttribute
{
    /**
     * @var AppResource
     */
    protected $resource;

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * Attribute constructor.
     *
     * @param AppResource $resource
     */
    public function __construct(
        AppResource $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * @return AdapterInterface
     */
    protected function getConnection()
    {
        if (!$this->connection) {
            $this->connection = $this->resource->getConnection('sales');
        }
        return $this->connection;
    }

    /**
     * Perform actions after object save
     *
     * @param Transaction $object
     * @param string $attribute
     * @return $this
     * @throws Exception
     */
    public function saveAttribute(Transaction $object, $attribute)
    {
        if (is_string($attribute)) {
            $attributes = [$attribute];
        } else {
            $attributes = $attribute;
        }
        if (is_array($attributes) && !empty($attributes)) {
            $this->getConnection()->beginTransaction();
            $data = array_intersect_key($object->getData(), array_flip($attributes));
            try {
                if ($object->getId() && !empty($data)) {
                    $this->getConnection()->update(
                        $object->getResource()->getMainTable(),
                        $data,
                        [$object->getResource()->getIdFieldName() . '= ?' => (int)$object->getId()]
                    );
                    $object->addData($data);
                }
                $this->getConnection()->commit();
            } catch (Exception $e) {
                $this->getConnection()->rollBack();
                throw $e;
            }
        }
        return $this;
    }
}
