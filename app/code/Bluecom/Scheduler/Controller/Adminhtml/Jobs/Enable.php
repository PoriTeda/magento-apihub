<?php
/**
 * Enable Controller
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
/**
 * Class Delete
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Controller\Adminhtml\Jobs
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
/**
 * Class Enable
 * @package Bluecom\Scheduler\Controller\Adminhtml\Jobs
 */
class Enable extends JobsAbstract
{
    /**
     * @return $this
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('job_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $model = $this->jobsFactory->create();
                $model->load($id);
                $model->setActive(1);
                $model->save();
                $this->messageManager->addSuccess(sprintf('Job: %s has been enabled.', $model->getJobCode()));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
