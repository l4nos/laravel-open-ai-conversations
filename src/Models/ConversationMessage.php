<?php

namespace Lanos\OpenAiConversations\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Lanos\OpenAiConversations\Concerns\HasOrderedUUID;

class ConversationMessage extends \Illuminate\Database\Eloquent\Model
{

    // IMPORTANT - USE SOFT DELETES TO MAKE MESSAGES NOT APPEND ONCE THEY NEED TO BE FORGOTTEN FOR TOKEN LIMIT REASONS,
    // BUT STILL ALLOW THEM TO BE VIEWABLE BY THE USER

    use HasOrderedUUID,
        SoftDeletes;

    protected $guarded = [];

    public function conversation(){
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Runs a rudimentary estimate of the token length of a request prior to submitting it
     * This can be compared against the response's token length
     * To have a more precise measurement, build a python microservice that uses the OpenAI Tokenizer library.
     * @param $message
     * @return int
     */
    public static function estimateTokenRequestLength($message){
        // Split by whitespace
        $whitespaceTokens = preg_split('/\s+/', $message);

        $tokens = [];

        foreach ($whitespaceTokens as $token) {
            // Split by punctuation
            $punctuationTokens = preg_split('/([.,!?]+)/', $token, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

            foreach ($punctuationTokens as $punctToken) {
                $tokens[] = $punctToken;
            }
        }

        return count($tokens);
    }

    protected $table = 'open_ai_conversation_messages';

}