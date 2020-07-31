<?php
namespace Riki\MessageQueue\Model\Consumer;

interface FailureExecutorInterface
{
    /**
     * @param $entityId
     * @return mixed
     */
    public function process($entityId);
}
