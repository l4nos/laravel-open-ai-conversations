<?php

namespace Lanos\OpenAiConversations\Concerns;

use Lanos\OpenAiConversations\Models\Conversation;

trait GPTModelData{

    private static $gptModels = [
        "gpt-4" => [
            "token_limit" => 8192,
            "expires" => false
        ],
        "gpt-4-0613" => [
            "token_limit" => 8192,
            "expires" => true
        ],
        "gpt-4-32k" => [
            "token_limit" => 32768,
            "expires" => false
        ],
        "gpt-4-32k-0613" => [
            "token_limit" => 32768,
            "expires" => true
        ],
        "gpt-4-0314" => [
            "token_limit" => 8192,
            "expires" => true
        ],
        "gpt-3.5-turbo" => [
            "token_limit" => 4096,
            "expires" => false
        ],
        "gpt-3.5-turbo-16k" => [
            "token_limit" => 16384,
            "expires" => false
        ],
        "gpt-3.5-turbo-0613	" => [
            "token_limit" => 4096,
            "expires" => true
        ],
        "gpt-3.5-turbo-16k-0613" => [
            "token_limit" => 16384,
            "expires" => true
        ]
    ];

    private static function retrieveModels(){
        if(config('open_ai_conversations.override_models')){
            return config('open_ai_conversations.override_models');
        }else{
            return Conversation::$gptModels;
        }
    }

    private static function assertTokenLimit($model){
        $gptModels = Conversation::retrieveModels();
        if(!isset($gptModels[$model])){
            throw new \Exception('You have specified an invalid GPT model');
        }
        return $gptModels[$model]['token_limit'];
    }

}
