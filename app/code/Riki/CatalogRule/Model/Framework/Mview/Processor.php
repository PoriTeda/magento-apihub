<?php

namespace Riki\CatalogRule\Model\Framework\Mview;

use Magento\Framework\Exception\CronException;

class Processor
    extends \Magento\Framework\Mview\Processor
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $timezone;

    /**
     * Mview Processor constructor.
     * @param \Magento\Framework\Mview\View\CollectionFactory $viewsFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     */
    public function __construct(
        \Magento\Framework\Mview\View\CollectionFactory $viewsFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->timezone = $timezone;
        parent::__construct($viewsFactory);
    }

    /**
     * @param string $expr
     * @param int $num
     * @return bool
     * @throws \Magento\Framework\Exception\CronException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function matchCronExpression($expr, $num)
    {
        // handle ALL match
        if ($expr === '*') {
            return true;
        }

        // handle multiple options
        if (strpos($expr, ',') !== false) {
            foreach (explode(',', $expr) as $e) {
                if ($this->matchCronExpression($e, $num)) {
                    return true;
                }
            }
            return false;
        }

        // handle modulus
        if (strpos($expr, '/') !== false) {
            $e = explode('/', $expr);
            if (sizeof($e) !== 2) {
                throw new CronException(__('Invalid cron expression, expecting \'match/modulus\': %1', $expr));
            }
            if (!is_numeric($e[1])) {
                throw new CronException(__('Invalid cron expression, expecting numeric modulus: %1', $expr));
            }
            $expr = $e[0];
            $mod = $e[1];
        } else {
            $mod = 1;
        }

        // handle all match by modulus
        if ($expr === '*') {
            $from = 0;
            $to = 60;
        } elseif (strpos($expr, '-') !== false) {
            // handle range
            $e = explode('-', $expr);
            if (sizeof($e) !== 2) {
                throw new CronException(__('Invalid cron expression, expecting \'from-to\' structure: %1', $expr));
            }

            $from = $this->getNumeric($e[0]);
            $to = $this->getNumeric($e[1]);
        } else {
            // handle regular token
            $from = $this->getNumeric($expr);
            $to = $from;
        }

        if ($from === false || $to === false) {
            throw new CronException(__('Invalid cron expression: %1', $expr));
        }

        return $num >= $from && $num <= $to && $num % $mod === 0;
    }

    /**
     * @param int|string $value
     * @return bool|int|string
     */
    public function getNumeric($value)
    {
        static $data = [
            'jan' => 1,
            'feb' => 2,
            'mar' => 3,
            'apr' => 4,
            'may' => 5,
            'jun' => 6,
            'jul' => 7,
            'aug' => 8,
            'sep' => 9,
            'oct' => 10,
            'nov' => 11,
            'dec' => 12,
            'sun' => 0,
            'mon' => 1,
            'tue' => 2,
            'wed' => 3,
            'thu' => 4,
            'fri' => 5,
            'sat' => 6,
        ];

        if (is_numeric($value)) {
            return $value;
        }

        if (is_string($value)) {
            $value = strtolower(substr($value, 0, 3));
            if (isset($data[$value])) {
                return $data[$value];
            }
        }

        return false;
    }

    /**
     * @param string $groupId
     * @return int
     */
    protected function getScheduleTimeInterval($groupId)
    {
        $scheduleAheadFor = (int)$this->scopeConfig->getValue(
            'system/cron/' . $groupId . '/' . \Magento\Cron\Observer\ProcessCronQueueObserver::XML_PATH_SCHEDULE_AHEAD_FOR,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        $scheduleAheadFor = $scheduleAheadFor * \Magento\Cron\Observer\ProcessCronQueueObserver::SECONDS_IN_MINUTE;

        return $scheduleAheadFor;
    }

    /**
     *  To check if cron can be ran in time or not
     *
     * @return bool
     * @throws CronException
     */
    protected function _isEnable(){

        $timeInterval = $this->getScheduleTimeInterval('index');
        $currentTime = $this->timezone->scopeTimeStamp();
        $timeAhead = $currentTime + $timeInterval;
        $e = $this->scopeConfig->getValue(
            \Riki\CatalogRule\Model\Indexer\Processor::CONFIG_CRON_EXPRESSION
        );

        if (!$e) {
            return false;
        }

        $e = preg_split('#\s+#', $e, null, PREG_SPLIT_NO_EMPTY);
        if (sizeof($e) < 5 || sizeof($e) > 6) {
            throw new CronException(__('Invalid cron expression: %1', $e));
        }

        for ($time = $currentTime; $time < $timeAhead; $time += \Magento\Cron\Observer\ProcessCronQueueObserver::SECONDS_IN_MINUTE) {
            $match = $this->matchCronExpression($e[0], strftime('%M', $time))
                && $this->matchCronExpression($e[1], strftime('%H', $time))
                && $this->matchCronExpression($e[2], strftime('%d', $time))
                && $this->matchCronExpression($e[3], strftime('%m', $time))
                && $this->matchCronExpression($e[4], strftime('%w', $time));

            if($match){
                return true;
            }
        }
        return false;
    }

    /**
     * Materialize all views by group (all views if empty)
     *
     * @param string $group
     * @return void
     */
    public function update($group = '')
    {
        foreach ($this->getViewsByGroup($group) as $view) {
            switch ($view->getId())
            {
                case 'catalogrule_rule':
                case 'catalogrule_product':
                    if($this->_isEnable()){
                        $view->update();
                    }
                    break;
                default:
                    $view->update();
                    break;
            }
        }
    }
}