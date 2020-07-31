<?php

namespace Riki\Subscription\Model\DB;

/**
 * Class Transaction
 * Copy from core to update some protected method for more flexible customization
 * @package Riki\Subscription\Model\DB
 */
class Transaction
{
    /**
     * Objects which will be involved to transaction
     *
     * @var array
     */
    public $objects = [];

    /**
     * Transaction objects array with alias key
     *
     * @var array
     */
    protected $objectsByAlias = [];

    /**
     * Callbacks array.
     *
     * @var array
     */
    protected $beforeCommitCallbacks = [];

    /**
     * Begin transaction for all involved object resources
     *
     * @return $this
     */
    public function startTransaction()
    {
        foreach ($this->objects as $object) {
            $object->getResource()->beginTransaction();
        }
        return $this;
    }

    /**
     * Commit transaction for all resources
     *
     * @return $this
     */
    public function commitTransaction()
    {
        foreach ($this->objects as $object) {
            $object->getResource()->commit();
        }
        return $this;
    }

    /**
     * Rollback transaction
     *
     * @return $this
     */
    public function rollbackTransaction()
    {
        foreach ($this->objects as $object) {
            $object->getResource()->rollBack();
        }
        return $this;
    }

    /**
     * Run all configured object callbacks
     *
     * @return $this
     */
    public function runCallbacks()
    {
        foreach ($this->beforeCommitCallbacks as $callback) {
            call_user_func($callback);
        }
        return $this;
    }

    /**
     * Adding object for using in transaction
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param string $alias
     * @return $this
     */
    public function addObject(\Magento\Framework\Model\AbstractModel $object, $alias = '')
    {
        $this->objects[] = $object;
        if (!empty($alias)) {
            $this->objectsByAlias[$alias] = $object;
        }
        return $this;
    }

    /**
     * Add callback function which will be called before commit transactions
     *
     * @param callback $callback
     * @return $this
     */
    public function addCommitCallback($callback)
    {
        $this->beforeCommitCallbacks[] = $callback;
        return $this;
    }

    /**
     * Initialize objects save transaction
     *
     * @return $this
     * @throws \Exception
     */
    public function save()
    {
        $this->startTransaction();
        $error = false;

        try {
            foreach ($this->objects as $object) {
                $object->save();
            }
        } catch (\Exception $e) {
            $error = $e;
        }

        if ($error === false) {
            try {
                $this->runCallbacks();
            } catch (\Exception $e) {
                $error = $e;
            }
        }

        if ($error) {
            $this->rollbackTransaction();
            throw $error;
        } else {
            $this->commitTransaction();
        }

        return $this;
    }

    /**
     * Initialize objects delete transaction
     *
     * @return $this
     * @throws \Exception
     */
    public function delete()
    {
        $this->startTransaction();
        $error = false;

        try {
            foreach ($this->objects as $object) {
                $object->delete();
            }
        } catch (\Exception $e) {
            $error = $e;
        }

        if ($error === false) {
            try {
                $this->runCallbacks();
            } catch (\Exception $e) {
                $error = $e;
            }
        }

        if ($error) {
            $this->rollbackTransaction();
            throw $error;
        } else {
            $this->commitTransaction();
        }
        return $this;
    }
}
