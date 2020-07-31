<?php
namespace Riki\AdvancedInventory\Logger;

class BaseLogger extends \Monolog\Logger
{
    /**
     * Get name of log file
     * @return mixed
     */
    public function getLogFileName()
    {
        $handlers = $this->getHandlers();
        if ($handlers) {
            foreach ($handlers as $handler) {
                if ($handler instanceof \Riki\AdvancedInventory\Logger\BaseHandler) {
                    return $handler->getLogFileName();
                }
            }
        }

        return '';
    }
}