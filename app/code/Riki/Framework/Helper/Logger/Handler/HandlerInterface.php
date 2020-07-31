<?php
namespace Riki\Framework\Helper\Logger\Handler;

interface HandlerInterface
{
    /**
     * Get log content
     *
     * @return string
     */
    public function getLogContent();
}