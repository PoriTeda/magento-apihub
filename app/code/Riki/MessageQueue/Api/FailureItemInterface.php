<?php
namespace Riki\MessageQueue\Api;

interface FailureItemInterface
{
    /**
     * @param $entityId
     * @return self
     */
    public function setEntityId($entityId);

    /**
     * @return int
     */
    public function getEntityId();

    /**
     * @param $name
     * @return self
     */
    public function setExecutor($name);

    /**
     * @return string
     */
    public function getExecutor();

    /**
     * @param $key
     * @return self
     */
    public function setMessageKey($key);

    /**
     * @return string
     */
    public function getMessageKey();
}
