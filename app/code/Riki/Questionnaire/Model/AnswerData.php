<?php
namespace Riki\Questionnaire\Model;

use Riki\Questionnaire\Api\Data\AnswerDataInterface;

class AnswerData implements AnswerDataInterface
{
    protected $_choices;
    
    protected $_content;
    
    public function setChoices(array $choices)
    {
        return $this->_choices = $choices;
    }

    /**
     * {@inheritDoc}
     */
    public function getChoices()
    {
        return $this->_choices;
    }

    public function setContent($content){
        return $this->_content = $content;
    }
    /**
     * {@inheritDoc}
     */
    public function getContent()
    {
        return $this->_content;
    }
}