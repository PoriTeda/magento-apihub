<?php


namespace Riki\Subscription\Logger;


class HandlerAddProductToProfile extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/subscription_add_product_to_profile.log';

}