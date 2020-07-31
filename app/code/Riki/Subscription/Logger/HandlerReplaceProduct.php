<?php
namespace Riki\Subscription\Logger;

class HandlerReplaceProduct extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/replace_product.log';

}