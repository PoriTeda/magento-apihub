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
namespace Riki\Framework\Model\Source;

/**
 * Class AbstractOption
 *
 * @category  RIKI
 * @package   Riki\Framework\Model
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
abstract class AbstractOption implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options
     *
     * @var array
     */
    protected $options;

    /**
     * Get label
     *
     * @param string $option option
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getLabel($option)
    {
        return $option;
    }

    /**
     * Get options to array
     *
     * ['label' => label, 'value' => value]
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->toArray() as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key
            ];
        }

        return $options;
    }

    /**
     * Get options to array
     * [value => label]
     *
     * @return array
     */
    public function toArray()
    {
        if ($this->options) {
            return $this->options;
        }

        $this->options = $this->prepare();

        return $this->options;
    }

    /**
     * Prepare options
     *
     * @return array
     */
    public function prepare()
    {
        $options = [];
        $reflection = new \ReflectionClass(get_class($this));
        foreach ($reflection->getConstants() as $constant) {
            $options[$constant] = $this->getLabel($constant);
        }

        return $options;
    }
}