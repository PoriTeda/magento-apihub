<?php
/**
 * Kill Controller
 *
 * PHP version 7
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Controller\Adminhtml\Schedules
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Bluecom\Scheduler\Controller\Adminhtml\Schedules;
use Psr\Log\LoggerInterface;
/**
 * Class Kill
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Controller\Adminhtml\Schedules
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Kill extends SchedulesAbstract
{
    CONST STATUS_KILLLED = 'killed';
    /**
     * @return $this
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('schedule_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $schedulerOjbect = $this->schedulerFactory->create();
                $schedulerOjbect->load($id);
                $this->kill($schedulerOjbect);
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e)
            {
                $this->logger->critical($e);
            }
        }
        else
        {
            $this->messageManager->addSuccess(sprintf('This job does not exist.'));
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @param \Bluecom\Scheduler\Model\Schedules $scheduler
     */
    public function kill(\Bluecom\Scheduler\Model\Schedules $scheduler)
    {
        $processId = $scheduler->getPid();
        if($this->checkPid($processId))
        {
            if (posix_kill($this->getPid(), SIGINT)) {
                $this->logger->info(sprintf('Sending SIGINT to job "%s" (id: %s)', $scheduler->getJobCode(), $scheduler->getId()));
            } else {
                $this->logger->info(sprintf('Error while sending SIGINT to job "%s" (id: %s)', $scheduler->getJobCode(), $scheduler->getId()));
            }
            // check if process terminates within 30 seconds
            $startTime = time();
            while (($waitTime = (time() - $startTime) < 30) && $this->checkPid($processId)) {
                sleep(2);
            }

            if ($this->checkPid($processId)) {
                // What, you're still alive? OK, time to say goodbye now. You had your chance...
                if (posix_kill($scheduler->getPid(), SIGKILL)) {
                    $this->logger->info(sprintf('Sending SIGKILL to job "%s" (id: %s)', $scheduler->getJobCode(), $scheduler->getId()));
                } else {
                    $this->logger->info(sprintf('Error while sending SIGKILL to job "%s" (id: %s)', $scheduler->getJobCode(), $scheduler->getId()));
                }
            } else {
                $this->logger->info(sprintf('Killed job "%s" (id: %s) with SIGINT. Job terminated after %s second(s)', $scheduler->getJobCode(), $scheduler->getId(), $waitTime));
            }

            if ($this->checkPid($processId)) {
                sleep(5);
                if ($this->checkPid($processId)) {
                    $this->logger->info(sprintf('Killed job "%s" (id: %s) is still alive!', $scheduler->getJobCode(), $scheduler->getId()));
                    return; // without setting the status to "killed"
                }
            }
            try
            {
                $scheduler->setStatus(self::STATUS_KILLLED)
                    ->setFinishedAt(strftime('%Y-%m-%d %H:%M:%S', time()))
                    ->save();

            }catch(\Exception $e)
            {
                $this->logger->critical($e);
            }
            $this->messageManager->addSuccess(sprintf('The job: %s has been killed.', $scheduler->getJobCode()));
        }
        else
        {
            $this->messageManager->addSuccess(sprintf('The job: %s does not exist.',$scheduler->getJobCode()));
        }
    }
    /**
     * @param $pid
     * @return bool
     */
    public function checkPid($pid)
    {
        return $pid && file_exists('/proc/' . $pid);
    }
}
