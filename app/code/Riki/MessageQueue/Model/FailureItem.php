<?php
namespace Riki\MessageQueue\Model;

use Riki\MessageQueue\Api\FailureItemInterface;

class FailureItem implements FailureItemInterface
{
    protected $entity_id;

    protected $executor_name;

    protected $key;


    /**
     * @param $entityId
     * @return mixed|void
     */
    public function setEntityId($entityId)
    {
        $this->entity_id = $entityId;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntityId()
    {
        return $this->entity_id;
    }

    /**
     * @param $name
     * @return self
     */
    public function setExecutor($name)
    {
        $this->executor_name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getExecutor()
    {
        return $this->executor_name;
    }

    /**
     * @return string
     */
    public function getMessageKey()
    {
        return $this->executor_name . '_' . $this->entity_id;
    }

    /**
     * @return string
     */
    public function setMessageKey($key)
    {
        $this->key = $key;
    }
}
