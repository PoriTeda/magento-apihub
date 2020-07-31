<?php
namespace Riki\Promo\Helper;

class Messages extends \Amasty\Promo\Helper\Messages
{
    /**
     * Added new session param to save free gift message for display
     *
     * @param $message
     * @param bool|true $isError
     * @param bool|false $showEachTime
     */
    public function showMessage($message, $isError = true, $showEachTime = false)
    {
        $displayErrors = $this->scopeConfig->isSetFlag(
            'ampromo/messages/display_error_messages',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$displayErrors && $isError)
            return;

        $displaySuccess = $this->scopeConfig->isSetFlag(
            'ampromo/messages/display_success_messages',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (!$displaySuccess && !$isError)
            return;

        $all = $this->messageManager->getMessages(false);

        foreach ($all as $existingMessage) {
            if ($message == $existingMessage->getText()) {
                return;
            }
        }

        if ($isError && $this->_request->getParam('debug')){
            $this->messageManager->addError($message);
        }
        else {
            $arr = $this->_checkoutSession->getAmpromoMessages();
            if (!is_array($arr)){
                $arr = [];
            }
            if (!in_array($message, $arr) || $showEachTime){

                $arrShow = $this->_checkoutSession->getShowAmpromoMessages();
                if (!is_array($arrShow)){
                    $arrShow = [];
                }

                $arr[] = $message;
                $arrShow[] = $message;
                $this->_checkoutSession->setAmpromoMessages($arr);
                $this->_checkoutSession->setShowAmpromoMessages($arrShow);
            }
        }
    }
}
