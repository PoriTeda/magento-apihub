<?php


namespace Riki\MessageQueue\Plugin;


class ForceQueueRunningMode
{
    public function beforeProcess($subject, $maxNumberOfMessages = null)
    {
        return [10000];
    }
}