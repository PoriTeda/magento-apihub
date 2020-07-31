<?php
namespace Riki\Framework\Helper\Logger;

class Monolog extends \Magento\Framework\Logger\Monolog
{
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $filesystem;

    /**
     * Base constructor.
     *
     * @param \Magento\Framework\Filesystem $filesystem
     * @param string $name
     * @param array $handlers
     * @param array $processors
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        $name = '',
        $handlers = [],
        $processors = []
    ) {
        $this->filesystem = $filesystem;
        foreach ($handlers as $key => $handler) {
            if (!$handler instanceof \Riki\Framework\Helper\Logger\Handler\HandlerInterface) {
                unset($handlers[$key]);
            }
        }

        parent::__construct($name, $handlers, $processors);
    }

    /**
     * Get log content
     *
     * @return string
     */
    public function getLogContent()
    {
        if (!$this->handlers) {
            return '';
        }

        foreach ($this->handlers as $handler) {
            if (method_exists($handler, 'getLogContent')) {
                return $handler->getLogContent();
            }
        }

        return '';
    }
}