<?php
/**
 * CvsPayment
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\CvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\CvsPayment\Ui\Component\Listing;

use Riki\CvsPayment\Api\ConstantInterface;

/**
 * Class FilterUrl
 *
 * @category  RIKI
 * @package   Riki\CvsPayment\Ui
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class FilterUrl implements \Iterator
{
    protected $data = [];

    /**
     * FilterUrl constructor.
     *
     * @param \Magento\Framework\Registry $registry registry
     */
    public function __construct(
        \Magento\Framework\Registry $registry
    ) {
        if ($registry->registry(ConstantInterface::REGISTRY_PENDING_CVS_30_DAYS)) {
            $this->data = [
                'status' => ConstantInterface::REGISTRY_PENDING_CVS_30_DAYS,
            ];
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function current()
    {
        return current($this->data);
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function next()
    {
        return next($this->data);
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function key()
    {
        return key($this->data);
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function valid()
    {
        $key = key($this->data);
        $var = ($key !== null && $key !== false);

        return $var;
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function rewind()
    {
        reset($this->data);
    }


}
