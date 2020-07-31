<?php
/**
 * JobSynchronize Cron
 *
 * PHP version 7
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Bluecom\Scheduler\Cron;
use Bluecom\Scheduler\Model\Sync;

/**
 * Class JobSynchronize
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Cron
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class JobSynchronize
{
    /**
     * @var Sync
     */
    protected $jobSynchronizeFactory;

    /**
     * JobSynchronize constructor.
     * @param Sync $sync
     */
    public function __construct
    (
        Sync $sync
    )
    {
        $this->jobSynchronizeFactory = $sync;
    }

    /**
     * execute the synchronize jobs
     */
    public function execute()
    {
        $this->jobSynchronizeFactory->SyncAllJobs();
    }
}