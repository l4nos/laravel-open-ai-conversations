<?php

namespace Lanos\OpenAiConversations\Enums;

enum OpenAiRole: string
{
    case USER      = 'user';
    case SYSTEM    = 'system';
    case ASSISTANT = 'assistant';
}
