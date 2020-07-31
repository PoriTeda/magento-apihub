<?php
namespace Riki\Subscription\Logger;

class HandlerCancelIncompleteGenerateProfileOrder extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/cancel_incomplete_generate_profile_order.log';
}
