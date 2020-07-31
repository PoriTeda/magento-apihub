<?php
/**
 * AdvancedInventory Import Stock
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\AdvancedInventory
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\AdvancedInventory\Logger;
use Monolog\Logger;

/**
 * Class HandlerInv2
 *
 * @category  RIKI
 * @package   Riki\AdvancedInventory
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class HandlerInv2 extends \Magento\Framework\Logger\Handler\Base
{
    const RIKI_IMPORT_STOCK_LOG_FILE_NAME = '/var/log/importstockinv2.log';
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;
    /**
     * @var string
     */
    protected $fileName = self::RIKI_IMPORT_STOCK_LOG_FILE_NAME;

}