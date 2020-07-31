<?php

namespace Riki\Theme\Plugin;

class RemoveDuplicateMessage
{
    public function afterGetSectionData($subject, $messages)
    {
        $uniqueMessages = [];

        foreach ($messages['messages'] as $message) {
            $uniqueMessages[md5($message['type'] . $message['text'])] = $message;
        }

        return ['messages' => array_values($uniqueMessages)];
    }
}
