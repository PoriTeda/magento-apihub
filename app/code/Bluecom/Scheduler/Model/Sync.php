<?php
/**
 * JobSynchronize Model
 *
 * PHP version 7
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Bluecom\Scheduler\Model;
use Bluecom\Scheduler\Model\ResourceModel\Jobs\CollectionFactory;
use Magento\Cron\Model\ConfigInterface;

/**
 * Class Sync
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Sync extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var JobsFactory
     */
    protected $jobFactory;
    /**
     * @var ConfigInterface
     */
    protected $cronConfig;
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;


    /**
     * Sync constructor.
     * @param \Bluecom\Scheduler\Model\JobsFactory $jobsFactory
     * @param CollectionFactory $collectionFactory
     * @param ConfigInterface $cronConfig
     */
    public function __construct(
        JobsFactory $jobsFactory,
        CollectionFactory $collectionFactory,
        ConfigInterface $cronConfig
    )
    {
        $this->jobFactory = $jobsFactory;
        $this->cronConfig = $cronConfig;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * synchronize all jobs between configuration and database
     */
    public function SyncAllJobs()
    {
        $jobs = $this->cronConfig->getJobs();
        $configedJobs = array();

        foreach($jobs as $jobGroupCode =>$jobDatas)
        {
            foreach($jobDatas as $jobData)
            {
                $configedJobs[] = $jobData['name'];
                $jobObject = $this->jobFactory->create();
                $existJob = $jobObject->loadByCode($jobData['name']);
                if($existJob) {
                    $jobObject = $existJob;
                } else {
                    $jobObject->setActive(1);
                }
                //update job
                $jobObject->setJobCode($jobData['name']);
                $jobObject->setGroupName($jobGroupCode);
                $jobObject->setMethodExecute($jobData['method']);
                $jobObject->setModelPath($jobData['instance']);
                $schedule = '';
                if(array_key_exists('schedule', $jobData))
                {
                    $schedule = $jobData['schedule'];
                }
                $jobObject->setExpression($schedule);
                $jobObject->setDefaultExpression($schedule);
                $jobObject->setLastExpression($schedule);
                $jobObject->save();
            }
        }
        //delete un-used jobs
        $this->removeJobs($configedJobs);
    }

    /**
     * @param $configedJobs
     */
    public function removeJobs($configedJobs)
    {
        $collections = $this->collectionFactory->create();
        if($collections->getSize())
        {
            foreach($collections as $job)
            {
                if(!in_array($job->getJobCode(),$configedJobs))
                {
                    $job->delete();
                }
            }
        }
    }
}