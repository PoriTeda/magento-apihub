<?php
namespace Riki\Framework\Helper;

class Filter
{
    /**
     * @var array
     */
    protected $callbacks;

    /**
     * @var \Zend\Filter\Word\CamelCaseToUnderscore
     */
    protected $camelCaseToUnderscoreFilter;

    /**
     * @var \Zend\Filter\Word\UnderscoreToCamelCase
     */
    protected $underscoreToCamelCaseFilter;

    /**
     * Filter constructor.
     *
     * @param \Zend\Filter\Word\CamelCaseToUnderscore $camelCaseToUnderscoreFilter
     * @param \Zend\Filter\Word\UnderscoreToCamelCase $underscoreToCamelCaseFilter
     */
    public function __construct(
        \Zend\Filter\Word\CamelCaseToUnderscore $camelCaseToUnderscoreFilter,
        \Zend\Filter\Word\UnderscoreToCamelCase $underscoreToCamelCaseFilter
    ) {
        $this->underscoreToCamelCaseFilter = $underscoreToCamelCaseFilter;
        $this->camelCaseToUnderscoreFilter = $camelCaseToUnderscoreFilter;

        $this->init();
    }

    public function init()
    {
        $this->callbacks = [];
    }

    /**
     * @param $value
     *
     * @return $this
     */
    public function handleCallback($value)
    {
        if (is_null($value)) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            list(, $target) = $trace;
            array_push($this->callbacks, $target['function']);
            return $this;
        }

        return $value;
    }

    /**
     * Convert string to underscore
     *
     * @param $value
     *
     * @return array|string|$this
     */
    public function camelCaseToUnderscore($value = null)
    {
        if (is_null($value)) {
            return $this->handleCallback($value);
        }

        return $this->camelCaseToUnderscoreFilter->filter($value);
    }

    /**
     * Convert string to camelcase
     *
     * @param $value
     *
     * @return array|string|$this
     */
    public function underscoreToCamelCase($value = null)
    {
        if (is_null($value)) {
            return $this->handleCallback($value);
        }

        return $this->underscoreToCamelCaseFilter->filter($value);
    }

    /**
     * Convert string to lower case
     *
     * @param $value
     *
     * @return string|$this
     */
    public function toLowercase($value = null)
    {
        if (is_null($value)) {
            return $this->handleCallback($value);
        }

        return strtolower($value);
    }

    /**
     * Convert string to upper case
     *
     * @param $value
     *
     * @return string|$this
     */
    public function toUppercase($value = null)
    {
        if (is_null($value)) {
            return $this->handleCallback($value);
        }

        return strtoupper($value);
    }

    /**
     * Filter value
     *
     * @param $value
     *
     * @return mixed
     */
    public function filter($value)
    {
        foreach ($this->callbacks as $callback) {
            $value = $this->$callback($value);
        }
        $this->init();

        return $value;
    }
}