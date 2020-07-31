<?php
/**
 * Delete Controller
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
/**
 * Class Delete
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Controller\Adminhtml\Schedules
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Delete extends SchedulesAbstract
{
    /**
     * @return $this
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('schedule_id');
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($id) {
            try {
                $model = $this->schedulerFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccess(__('Job has been deleted.'));
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
            }
        }
        return $resultRedirect->setPath('*/*/');
    }
}
