<?php

namespace Riki\EmailMarketing\Model\MailLog;

use Riki\EmailMarketing\Api\MailLog\ItemInterface;

class Item implements ItemInterface
{

    /**
     * @var int
     */
    protected $logId;

    /**
     * @return int
     */
    public function getLogId()
    {
        return $this->logId;
    }

    /**
     * @param int $logId
     *
     * @return $this
     */
    public function setLogId($logId)
    {
        $this->logId = $logId;

        return $this;
    }
}