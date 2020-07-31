<?php
/**
 * Jobs Model
 *
 * PHP version 7
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Bluecom\Scheduler\Model;
/**
 * Class Jobs
 *
 * @category  Bluecom\Scheduler
 * @package   Bluecom\Scheduler\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Jobs extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Bluecom\Scheduler\Model\ResourceModel\Jobs');
    }

    /**
     * @param string $jobCode
     * @return bool
     */
    public function loadByCode($jobCode)
    {
        $collection = $this->getResourceCollection()
            ->addFieldToFilter('job_code', $jobCode)
            ->setPageSize(1)
            ->setCurPage(1);

        foreach ($collection as $object) {
            return $object;
        }
        return false;
    }
}