<?php
/**
 * Basic Setup Module
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\BasicSetup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\BasicSetup\Command;
use Symfony\Component\Console\Command\Command;

/**
 * Class AbstractCommand
 *
 * @category  RIKI
 * @package   Riki\BasicSetup
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class AbstractCommand extends Command
{
    /**
     * @var
     */
    protected $objectManager;

    /**
     * AbstractCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return mixed
     */
    protected function getObjectManager()
    {
        return $this->objectManager;
    }

}