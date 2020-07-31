<?php

namespace Riki\AdvancedInventory\Controller\Adminhtml\ReAssignation;

use Bluecom\Scheduler\Controller\Adminhtml\Jobs\JobsAbstract;
use Magento\Cron\Model\Schedule;
use Riki\AdvancedInventory\Controller\Adminhtml\ReAssignation;

class ScheduleNow extends JobsAbstract
{
    const JOB_CODE = 'riki_advanced_inventory_reassignation';

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Bluecom\Scheduler\Model\Jobs $job */
        $job = $this->jobsFactory->create();
        $job = $job->loadByCode(self::JOB_CODE);

        if ($job) {
            $params = $this->getRequest()->getParams();
            $params['job_id'] = $job->getId();

            $this->getRequest()->setParams($params);
        }

        $id = $this->getRequest()->getParam('job_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $job = $this->jobsFactory->create()->load($id);
                if ($job->getActive()) {
                    $schedule = $this->scheduleFactory->create();
                    try {
                        $schedule->setStatus(Schedule::STATUS_PENDING)
                            ->setJobCode($job->getJobCode())
                            ->setCreatedAt(strftime('%Y-%m-%d %H:%M:%S', $this->timezone->scopeTimeStamp()))
                            ->setScheduledAt(strftime('%Y-%m-%d %H:%M:%S', $this->timezone->scopeTimeStamp() + 15))
                            ->save();
                    } catch (\Exception $e) {
                        $this->messageManager->addError($e->getMessage());
                    }
                    $this->messageManager->addSuccess(sprintf('The job: %s has been scheduled.', $job->getJobCode()));
                } else {
                    $this->messageManager->addError(sprintf('Job: %s has been disabled.', $job->getJobCode()));
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(ReAssignation::REASSIGNATION_RESOURCE);
    }
}
