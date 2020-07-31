<?php

namespace Riki\Theme\Controller\Result;

use Magento\Framework\Translate\InlineInterface;

class MessagePlugin extends \Magento\Theme\Controller\Result\MessagePlugin
{
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;

    /**
     * MessagePlugin constructor.
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\View\Element\Message\InterpretationStrategyInterface $interpretationStrategy
     * @param \Magento\Framework\Serialize\Serializer\Json|null $serializer
     * @param InlineInterface|null $inlineTranslate
     * @param \Magento\Framework\Escaper $escaper
     */
    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\View\Element\Message\InterpretationStrategyInterface $interpretationStrategy,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null,
        InlineInterface $inlineTranslate = null,
        \Magento\Framework\Escaper $escaper
    ) {
        parent::__construct(
            $cookieManager,
            $cookieMetadataFactory,
            $messageManager,
            $interpretationStrategy,
            $serializer,
            $inlineTranslate
        );

        $this->escaper = $escaper;
    }

    /**
     * @inheritDoc
     */
    protected function getMessages()
    {
        $messages = parent::getMessages();
        $uniqueMessages = [];
        foreach ($messages as $message) {
            $message['type'] = $this->escaper->escapeHtml($message['type']);
            $message['text'] = $this->escaper->escapeHtml($message['text']);
            $uniqueMessages[md5($message['type'] . $message['text'])] = $message;
        }
        return array_values($uniqueMessages);
    }
}
