<?php
/**
 * Framework
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\Framework
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\Framework\Helper\Container;

/**
 * Class Request
 *
 * @category  RIKI
 * @package   Riki\Framework\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Request extends AbstractContainer
{
    /**
     * Request
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Request constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        $this->request = $context->getRequest();
        parent::__construct($context);
        $this->setData([]);
    }


    /**
     * Set data into request
     *
     * @param string $key   key
     * @param mixed  $value value
     *
     * @return $this
     */
    public function setData($key, $value = null)
    {
        $data = $this->request->getParam($this->getIdentifier());
        if (is_null($value) && is_array($key)) {
            $data = $key;
        } else {
            $data[$key] = $value;
        }
        $this->request->setParam($this->getIdentifier(), $data);

        return $this;
    }

    /**
     * Get data from request
     *
     * @param null $key key
     *
     * @return mixed|null
     */
    public function getData($key = null)
    {
        $data = $this->request->getParam($this->getIdentifier());

        return is_null($key)
            ? $data
            : (isset($data[$key]) ? $data[$key] : null);
    }
}