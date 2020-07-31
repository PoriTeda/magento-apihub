<?php
/**
 * Index Jobs Controller
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
 * Class Index
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Controller\Adminhtml\Jobs
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Index extends JobsAbstract
{
    /**
     * Index action
     *
     * @return void
     */
    public function execute()
    {
        $resultPage = $this->initResultPage();
        $resultPage->getConfig()->getTitle()->prepend(__('Jobs configuration'));
        return $resultPage;
    }
}
