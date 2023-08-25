<?php

namespace Lanos\OpenAiConversations\Concerns;

use Illuminate\Support\Str;

trait HasOrderedUUID
{
    public function initializeHasOrderedUUID()
    {
        $this->attributes['id'] = Str::orderedUuid();
    }

    /**
     * @return string
     */
    public function getKeyType()
    {
        return 'string';
    }

    /**
     * @return false
     */
    public function getIncrementing()
    {
        return false;
    }
}
