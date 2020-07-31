<?php

namespace Riki\CsvOrderMultiple\Controller\Adminhtml\Import;
use Magento\Cron\Model\Schedule;

class RunCron extends \Bluecom\Scheduler\Controller\Adminhtml\Jobs\Schenow
{
    /**
     * @return bool
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_CsvOrderMultiple::import_order_csv_run_cron');
    }

    /**
     * @return $this
     */
    public function execute()
    {
        $jobId= $this->getJobIdByCode('import_order_csv_multiple');
        $this->getRequest()->setParam('job_id',$jobId);
        return parent::execute();
    }

    /**
     * @param $jobCode
     * @return null
     */
    public function getJobIdByCode($jobCode)
    {
        try {
            $job = $this->jobsFactory->create()->getCollection()->addFieldToFilter('job_code',$jobCode)->getFirstItem();
            if($job && $job->getJobId())
            {
                return $job->getJobId();
            }
        } catch (\Exception $e)
        {
            return null;
        }
    }


}
