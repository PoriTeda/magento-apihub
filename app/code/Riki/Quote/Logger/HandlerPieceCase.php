<?php


namespace Riki\Quote\Logger;


class HandlerPieceCase extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * @var int
     */
    protected $loggerType = \Monolog\Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = '/var/log/add_product_piece_case.log';

}