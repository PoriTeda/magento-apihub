<?php

namespace Riki\Framework\Controller\Result;

use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;

class TaintJson extends \Magento\Framework\Controller\Result\Json
{
    protected $_taint = 'for(;;);';

    /**
     * @param string $taint
     */
    public function setTaint($taint)
    {
        $this->_taint = $taint;
    }

    /**
     * Add taint string to beginning of the response.
     *
     * @param HttpResponseInterface $response
     *
     * @return $this|\Magento\Framework\Controller\Result\Json
     */
    protected function render(HttpResponseInterface $response)
    {
        $this->translateInline->processResponseBody($this->json, true);
        $response->setHeader('Content-Type', 'application/json', true);
        $response->setBody($this->_taint . $this->json);
        return $this;
    }
}
