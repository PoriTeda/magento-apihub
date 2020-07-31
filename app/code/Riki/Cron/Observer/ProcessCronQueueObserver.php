<?php

namespace Riki\Cron\Observer;

use Magento\Framework\Console\CLI;
use Riki\Cron\Helper\CronNameHelper;
use Magento\Cron\Model\Schedule;
use Riki\EmailMarketing\Helper\Email;
use Bluecom\Scheduler\Model\ResourceModel\Jobs\CollectionFactory;
use Magento\Framework\Profiler\Driver\Standard\StatFactory;
use Magento\Framework\App\State;
use Magento\Framework\Profiler\Driver\Standard\Stat;

class ProcessCronQueueObserver extends \Magento\Cron\Observer\ProcessCronQueueObserver
{
    /**
     * @var Email
     */
    protected $emailHelper;
    /**
     * @var CollectionFactory
     */
    protected $jobsCollectionFactory;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * @var \Magento\Framework\Lock\LockManagerInterface
     */
    private $lockManager;

    /**
     * @var array
     */
    private $invalid = [];

    /**
     * @var Stat
     */
    private $statProfiler;

    /**
     * ProcessCronQueueObserver constructor.
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Cron\Model\ScheduleFactory $scheduleFactory
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Cron\Model\ConfigInterface $config
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Console\Request $request
     * @param \Magento\Framework\ShellInterface $shell
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Framework\Process\PhpExecutableFinderFactory $phpExecutableFinderFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param State $state
     * @param StatFactory $statFactory
     * @param \Magento\Framework\Lock\LockManagerInterface $lockManager
     * @param Email $emailHelper
     * @param CollectionFactory $collectionFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Cron\Model\ScheduleFactory $scheduleFactory,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Cron\Model\ConfigInterface $config,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Console\Request $request,
        \Magento\Framework\ShellInterface $shell,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Framework\Process\PhpExecutableFinderFactory $phpExecutableFinderFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\State $state,
        StatFactory $statFactory,
        \Magento\Framework\Lock\LockManagerInterface $lockManager,
        Email $emailHelper,
        CollectionFactory $collectionFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    )
    {
        $this->emailHelper = $emailHelper;
        $this->jobsCollectionFactory = $collectionFactory;
        $this->logger = $logger;
        $this->state = $state;
        $this->statProfiler = $statFactory->create();
        $this->lockManager = $lockManager;
        parent::__construct(
            $objectManager,
            $scheduleFactory,
            $cache,
            $config,
            $scopeConfig,
            $request,
            $shell,
            $dateTime,
            $phpExecutableFinderFactory,
            $logger,
            $state,
            $statFactory,
            $lockManager,
            $timezone
        );
    }

    /**
     * Fix bug which causes cron re-run multiple times
     *
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @throws \ErrorException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $currentTime = $this->timezone->scopeTimeStamp();
        $jobGroupsRoot = $this->_config->getJobs();

        // end command transaction to track cron job transaction
        if (extension_loaded('newrelic')) {
            newrelic_end_transaction(true);
        }

        // sort jobs groups to start from used in separated process
        uksort(
            $jobGroupsRoot,
            function ($a, $b) {
                return $this->getCronGroupConfigurationValue($b, 'use_separate_process')
                    - $this->getCronGroupConfigurationValue($a, 'use_separate_process');
            }
        );

        $phpPath = $this->phpExecutableFinder->find() ?: 'php';

        foreach ($jobGroupsRoot as $groupId => $jobsRoot) {
            if (!$this->isGroupInFilter($groupId)) {
                continue;
            }
            if ($this->_request->getParam(self::STANDALONE_PROCESS_STARTED) !== '1'
                && $this->getCronGroupConfigurationValue($groupId, 'use_separate_process') == 1
            ) {
                $this->_shell->execute(
                    $phpPath . ' %s cron:run --group=' . $groupId . ' --' . Cli::INPUT_KEY_BOOTSTRAP . '='
                    . self::STANDALONE_PROCESS_STARTED . '=1',
                    [
                        BP . '/bin/magento'
                    ]
                );
                continue;
            }

            $this->lockGroup(
                $groupId,
                function ($groupId) use ($currentTime, $jobsRoot) {
                    $this->cleanupJobs($groupId, $currentTime);
                    $this->generateSchedules($groupId);
                    $this->processPendingJobs($groupId, $jobsRoot, $currentTime);
                }
            );
        }
    }
    /**
     * Process pending jobs.
     *
     * @param string $groupId
     * @param array $jobsRoot
     * @param int $currentTime
     */
    private function processPendingJobs($groupId, $jobsRoot, $currentTime)
    {
        $procesedJobs = [];
        $pendingJobs = $this->getPendingSchedules($groupId);
        /** @var \Magento\Cron\Model\Schedule $schedule */
        foreach ($pendingJobs as $schedule) {
            if (isset($procesedJobs[$schedule->getJobCode()])) {
                // process only on job per run
                continue;
            }
            $jobConfig = isset($jobsRoot[$schedule->getJobCode()]) ? $jobsRoot[$schedule->getJobCode()] : null;
            if (!$jobConfig) {
                continue;
            }

            $scheduledTime = strtotime($schedule->getScheduledAt());
            if ($scheduledTime !== false && $scheduledTime > $currentTime) {
                continue;
            }

            try {
                if ($schedule->tryLockJob())
                {
                    //get job name after change if have
                    $jobName = CronNameHelper::changeCronName($schedule->getJobCode());
                    if (extension_loaded('newrelic')) {
                        newrelic_start_transaction(ini_get('newrelic.appname'));
                        newrelic_name_transaction($jobName);
                    }

                    $this->_runJob($scheduledTime, $currentTime, $jobConfig, $schedule, $groupId);

                    if (extension_loaded('newrelic')) {
                        newrelic_end_transaction();
                    }
                }
            } catch (\Exception $e) {
                $this->processError($schedule, $e);
            }
            if ($schedule->getStatus() === Schedule::STATUS_SUCCESS) {
                $procesedJobs[$schedule->getJobCode()] = true;
            }
            $schedule->save();
        }
    }
    /**
     * Process error messages.
     *
     * @param Schedule $schedule
     * @param \Exception $exception
     * @return void
     */
    private function processError(\Magento\Cron\Model\Schedule $schedule, \Exception $exception)
    {
        $schedule->setMessages($exception->getMessage());
        if ($schedule->getStatus() === Schedule::STATUS_ERROR) {
            $this->logger->critical($exception);
        }
        if ($schedule->getStatus() === Schedule::STATUS_MISSED
            && $this->state->getMode() === State::MODE_DEVELOPER
        ) {
            $this->logger->info($schedule->getMessages());
        }
    }
    /**
     * Return job collection from data base with status 'pending'.
     *
     * @param string $groupId
     * @return \Magento\Cron\Model\ResourceModel\Schedule\Collection
     */
    private function getPendingSchedules($groupId)
    {
        $jobs = $this->_config->getJobs();
        $pendingJobs = $this->_scheduleFactory->create()->getCollection();
        $pendingJobs->addFieldToFilter('status', Schedule::STATUS_PENDING);
        $pendingJobs->addFieldToFilter('job_code', ['in' => array_keys($jobs[$groupId])]);
        return $pendingJobs;
    }

    /**
     * Lock group
     *
     * It should be taken by standalone (child) process, not by the parent process.
     *
     * @param int $groupId
     * @param callable $callback
     *
     * @return void
     */
    private function lockGroup($groupId, callable $callback)
    {

        if (!$this->lockManager->lock(self::LOCK_PREFIX . $groupId, self::LOCK_TIMEOUT)) {
            $this->logger->warning(
                sprintf(
                    "Could not acquire lock for cron group: %s, skipping run",
                    $groupId
                )
            );
            return;
        }
        try {
            $callback($groupId);
        } finally {
            $this->lockManager->unlock(self::LOCK_PREFIX . $groupId);
        }
    }

    /**
     * Generate cron schedule
     *
     * @param string $groupId
     * @return $this
     */
    private function generateSchedules($groupId)
    {
        /**
         * check if schedule generation is needed
         */
        $lastRun = (int)$this->_cache->load(self::CACHE_KEY_LAST_SCHEDULE_GENERATE_AT . $groupId);
        $rawSchedulePeriod = (int)$this->getCronGroupConfigurationValue(
            $groupId,
            self::XML_PATH_SCHEDULE_GENERATE_EVERY
        );
        $schedulePeriod = $rawSchedulePeriod * self::SECONDS_IN_MINUTE;
        if ($lastRun > $this->timezone->scopeTimeStamp() - $schedulePeriod) {
            return $this;
        }

        /**
         * save time schedules generation was ran with no expiration
         */
        $this->_cache->save(
            $this->timezone->scopeTimeStamp(),
            self::CACHE_KEY_LAST_SCHEDULE_GENERATE_AT . $groupId,
            ['crontab'],
            null
        );

        $schedules = $this->getPendingSchedules($groupId);
        $exists = [];
        /** @var Schedule $schedule */
        foreach ($schedules as $schedule) {
            $exists[$schedule->getJobCode() . '/' . $schedule->getScheduledAt()] = 1;
        }

        /**
         * generate global crontab jobs
         */
        $jobs = $this->_config->getJobs();
        $this->invalid = [];
        $this->_generateJobs($jobs[$groupId], $exists, $groupId);
        $this->cleanupScheduleMismatches();

        return $this;
    }
    /**
     * Clean up scheduled jobs that do not match their cron expression anymore.
     *
     * This can happen when you change the cron expression and flush the cache.
     *
     * @return $this
     */
    private function cleanupScheduleMismatches()
    {
        /** @var \Magento\Cron\Model\ResourceModel\Schedule $scheduleResource */
        $scheduleResource = $this->_scheduleFactory->create()->getResource();
        foreach ($this->invalid as $jobCode => $scheduledAtList) {
            $scheduleResource->getConnection()->delete($scheduleResource->getMainTable(), [
                'status = ?' => Schedule::STATUS_PENDING,
                'job_code = ?' => $jobCode,
                'scheduled_at in (?)' => $scheduledAtList,
            ]);
        }
        return $this;
    }

    /**
     * Get CronGroup Configuration Value.
     *
     * @param string $groupId
     * @param string $path
     * @return int
     */
    private function getCronGroupConfigurationValue($groupId, $path)
    {
        return $this->_scopeConfig->getValue(
            'system/cron/' . $groupId . '/' . $path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Clean expired jobs
     *
     * @param string $groupId
     * @param int $currentTime
     * @return void
     */
    private function cleanupJobs($groupId, $currentTime)
    {
        // check if history cleanup is needed
        $lastCleanup = (int)$this->_cache->load(self::CACHE_KEY_LAST_HISTORY_CLEANUP_AT . $groupId);
        $historyCleanUp = (int)$this->getCronGroupConfigurationValue($groupId, self::XML_PATH_HISTORY_CLEANUP_EVERY);
        if ($lastCleanup > $this->timezone->scopeTimeStamp() - $historyCleanUp * self::SECONDS_IN_MINUTE) {
            return $this;
        }
        // save time history cleanup was ran with no expiration
        $this->_cache->save(
            $this->timezone->scopeTimeStamp(),
            self::CACHE_KEY_LAST_HISTORY_CLEANUP_AT . $groupId,
            ['crontab'],
            null
        );

        $this->cleanupDisabledJobs($groupId);

        $historySuccess = (int)$this->getCronGroupConfigurationValue($groupId, self::XML_PATH_HISTORY_SUCCESS);
        $historyFailure = (int)$this->getCronGroupConfigurationValue($groupId, self::XML_PATH_HISTORY_FAILURE);
        $historyLifetimes = [
            Schedule::STATUS_SUCCESS => $historySuccess * self::SECONDS_IN_MINUTE,
            Schedule::STATUS_MISSED => $historyFailure * self::SECONDS_IN_MINUTE,
            Schedule::STATUS_ERROR => $historyFailure * self::SECONDS_IN_MINUTE,
            Schedule::STATUS_PENDING => max($historyFailure, $historySuccess) * self::SECONDS_IN_MINUTE,
        ];

        $jobs = $this->_config->getJobs()[$groupId];
        $scheduleResource = $this->_scheduleFactory->create()->getResource();
        $connection = $scheduleResource->getConnection();
        $count = 0;
        foreach ($historyLifetimes as $status => $time) {
            $count += $connection->delete(
                $scheduleResource->getMainTable(),
                [
                    'status = ?' => $status,
                    'job_code in (?)' => array_keys($jobs),
                    'created_at < ?' => $connection->formatDate($currentTime - $time)
                ]
            );
        }

        if ($count) {
            $this->logger->info(sprintf('%d cron jobs were cleaned', $count));
        }
    }

    /**
     * Clean up scheduled jobs that are disabled in the configuration.
     *
     * This can happen when you turn off a cron job in the config and flush the cache.
     *
     * @param string $groupId
     * @return void
     */
    private function cleanupDisabledJobs($groupId)
    {
        $jobs = $this->_config->getJobs();
        $jobsToCleanup = [];
        foreach ($jobs[$groupId] as $jobCode => $jobConfig) {
            if (!$this->getCronExpression($jobConfig)) {
                /** @var \Magento\Cron\Model\ResourceModel\Schedule $scheduleResource */
                $jobsToCleanup[] = $jobCode;
            }
        }

        if (count($jobsToCleanup) > 0) {
            $scheduleResource = $this->_scheduleFactory->create()->getResource();
            $count = $scheduleResource->getConnection()->delete(
                $scheduleResource->getMainTable(),
                [
                    'status = ?' => Schedule::STATUS_PENDING,
                    'job_code in (?)' => $jobsToCleanup,
                ]
            );

            $this->logger->info(sprintf('%d cron jobs were cleaned', $count));
        }
    }

    /**
     * Get cron expression of cron job.
     *
     * @param array $jobConfig
     * @return null|string
     */
    private function getCronExpression($jobConfig)
    {
        $cronExpression = null;
        if (isset($jobConfig['config_path'])) {
            $cronExpression = $this->getConfigSchedule($jobConfig) ?: null;
        }

        if (!$cronExpression) {
            if (isset($jobConfig['schedule'])) {
                $cronExpression = $jobConfig['schedule'];
            }
        }
        return $cronExpression;
    }


    /**
     * Is Group In Filter.
     *
     * @param string $groupId
     * @return bool
     */
    private function isGroupInFilter($groupId): bool
    {
        return !($this->_request->getParam('group') !== null
            && trim($this->_request->getParam('group'), "'") !== $groupId);
    }
}
