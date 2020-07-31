<?php

namespace Riki\EmailMarketing\Api\MailLog;

interface ItemInterface
{
    /**
     * @return int
     */
    public function getLogId();

    /**
     * @param int $logId
     *
     * @return $this
     */
    public function setLogId($logId);
}