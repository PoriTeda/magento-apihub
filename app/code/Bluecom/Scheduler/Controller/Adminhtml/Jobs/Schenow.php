<?php
/**
 * Schenow Controller
 *
 * PHP version 7
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Controller\Adminhtml\Jobs
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Bluecom\Scheduler\Controller\Adminhtml\Jobs;

use Magento\Cron\Model\Schedule;

/**
 * Class Schenow
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Controller\Adminhtml\Jobs
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Schenow extends JobsAbstract
{
    /**
     * @return $this
     */
    public function execute()
    {
        $createdTime = strftime('%Y-%m-%d %H:%M:%S', $this->timezone->scopeTimeStamp());
        $executeTime = strftime('%Y-%m-%d %H:%M:%S', $this->timezone->scopeTimeStamp() + 30);
        $id = $this->getRequest()->getParam('job_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $jobs = $this->jobsFactory->create()->load($id);
                if ($jobs->getActive()) {
                    $schedules = $this->scheduleFactory->create();
                    $schedules->setStatus(Schedule::STATUS_PENDING)
                        ->setJobCode($jobs->getJobCode())
                        ->setCreatedAt($createdTime)
                        ->setScheduledAt($executeTime);
                    $schedules->save();
                    $this->messageManager->addSuccess(sprintf('Job: %s has been scheduled.', $jobs->getJobCode()));
                } else {
                    $this->messageManager->addError(sprintf('Job: %s has been disabled.', $jobs->getJobCode()));
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
