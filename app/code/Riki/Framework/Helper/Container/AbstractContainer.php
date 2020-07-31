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
 * Class AbstractContainer
 *
 * @category  RIKI
 * @package   Riki\Framework\Helper
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
abstract class AbstractContainer extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Identifier
     *
     * @var string
     */
    protected $identifier;

    /**
     * AbstractContainer constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
        $this->setIdentifier(get_class($this));
    }

    /**
     * Getter for identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Setter for identifier
     *
     * @param string $identifier identifier
     *
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Set data
     *
     * @param string $key   key
     * @param null   $value value
     *
     * @return $this
     */
    public function setData($key, $value = null)
    {
        return $this;
    }

    /**
     * Get data
     *
     * @param null $key key
     *
     * @return mixed
     */
    public function getData($key = null)
    {
        return $this;
    }

    /**
     * Unset data
     *
     * @param null $key key
     *
     * @return $this
     */
    public function unsData($key = null)
    {
        $data = $this->getData();

        if (is_null($key)) {
            $this->setData([]);
        } elseif (isset($data[$key])) {
            unset($data[$key]);
            $this->setData($data);
        }

        return $this;
    }

    /**
     * Isset data
     *
     * @param string $key key
     *
     * @return bool
     */
    public function hasData($key)
    {
        $data = $this->getData();
        return isset($data[$key]);
    }

    /**
     * Insert data into array data
     *
     * @param string $key   key
     * @param mixed  $value value
     *
     * @return $this
     */
    public function pushData($key, $value)
    {
        $data = $this->getData($key);
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if (is_array($data)) {
                    $data[is_numeric($k) ? count($data) : $k] = $v;
                } elseif (is_null($data)) {
                    $data = [$k => $v];
                } else {
                    $data = $v;
                }
            }
        } else {
            if (is_array($data)) {
                $data[] = $value;
            } elseif (is_null($data)) {
                $data = [$value];
            } else {
                $data = $value;
            }
        }

        $this->setData($key, $data);
        return $this;
    }
}