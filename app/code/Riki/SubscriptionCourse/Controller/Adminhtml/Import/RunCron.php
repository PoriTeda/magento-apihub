<?php

namespace Riki\SubscriptionCourse\Controller\Adminhtml\Import;

class RunCron extends \Bluecom\Scheduler\Controller\Adminhtml\Jobs\Schenow
{
    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionCourse::run_cron');
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $jobId = $this->getJobIdByCode('import_subscription_course');
        $this->getRequest()->setParam('job_id', $jobId);
        return parent::execute();
    }

    /**
     * @param $jobCode
     * @return null
     */
    protected function getJobIdByCode($jobCode)
    {
        try {
            $job = $this->jobsFactory->create()->getCollection()
                ->addFieldToFilter('job_code', $jobCode)
                ->setPageSize(1)
                ->getFirstItem();
            if ($job && $job->getJobId()) {
                return $job->getJobId();
            }
        } catch (\Exception $e) {
            return null;
        }
    }
}
