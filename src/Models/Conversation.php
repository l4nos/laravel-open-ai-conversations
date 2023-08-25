<?php

namespace Lanos\OpenAiConversations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Lanos\OpenAiConversations\Concerns\GPTModelData;
use Exception;
use Lanos\OpenAiConversations\Concerns\HasOrderedUUID;
use OpenAI\Laravel\Facades\OpenAI;

class Conversation extends \Illuminate\Database\Eloquent\Model
{

    use HasOrderedUUID,
        SoftDeletes,
        GPTModelData;

    protected static function booted()
    {
        static::creating(function ($model) {
            // APPLY DEFAULT N
            $model->n_value = config('open_ai_conversations.default_n_value');
            // APPLY DEFAULT MODEL
            $model->model = config('open_ai_conversations.default_model');

            // APPLY DEFAULT TOKEN LIMIT
            $maxTokenLimit = Conversation::assertTokenLimit(config('open_ai_conversations.default_model'));
            if(config('open_ai_conversations.default_conversation_token_limit') === 'MAX'){
                $model->token_limit = $maxTokenLimit;
            }

            // IN CASE DEVELOPER TRIES TO CONFIGURE HIGHER TOKEN LIMIT THAN IS ALLOWED
            if(config('open_ai_conversations.default_conversation_token_limit') !== 'MAX' && config('open_ai_conversations.default_conversation_token_limit') > $maxTokenLimit){
                throw new \Exception('Your token limit is too high for the chosen GPT model');
            }

        });
    }

    public function messages(){
        return $this->hasMany(ConversationMessage::class);
    }

    /**
     * Remove earlier messages to enforce token limit before sending the whole response to OpenAI.
     * This keeps us from exceeding limits with the API
     * It does mean the conversation will "forget" earlier messages
     */
    private function enforceTokenLimit($newMessageLength)
    {
        $currentTokens = $this->messages->sum('actual_token_length') + $newMessageLength;

        while ($currentTokens > $this->token_limit) {
            // Remove the earliest message
            $this->messages->first()->delete();

            // Refresh and re-check token count
            $this->load('messages');
            $currentTokens = $this->messages->sum('actual_token_length') + $newMessageLength;
        }
    }

    /**
     * Main public function for asking a question on a conversation
     * @param $content
     * @return Model|HasMany|object|null
     */
    public function askQuestion($content){

        $estimatedTokenLength = ConversationMessage::estimateTokenRequestLength($content);

        // MAKE SURE THERE WILL BE ENOUGH TOKENS AVAILABLE FOR THIS, IF NOT, FORGET SOME MESSAGES
        $this->enforceTokenLimit($estimatedTokenLength);

        // OK, NOW WE GOT THE LIMIT COVERED, LET'S PREPARE THE REQUEST
        $payload = $this->prepareMessagePayload($content);

        $commitRequest = OpenAI::chat()->create([
            'model' => $this->model,
            'messages' => $payload,
        ]);

        if(isset($commitRequest['choices'])){

            // APPEND THE QUESTION
            $this->messages()->create([
                "estimated_token_length" => $estimatedTokenLength,
                "actual_token_length" => $commitRequest['usage']['prompt_tokens'],
                "is_from_user" => true,
                "content" => $content
            ]);

            $this->messages()->create([
                "estimated_token_length" => 0,
                "actual_token_length" => $commitRequest['usage']['completion_tokens'],
                "is_from_user" => false,
                "content" => $commitRequest['choices'][0]['message']['content']
            ]);

            // RETURN THE NEW MESSAGE
            return $this->messages()->orderBy('id', 'desc')->first();

        }else{
            throw new Exception('GPT Request Failed');
        }

    }


    /**
     * Appends a new message to the formatted conversation ready to send to OpenAI
     * @param $content
     * @return mixed[]
     */
    private function prepareMessagePayload($content){
        $messages = $this->preparePreviousConversation();
        $messages[] = [
            "role" => "user",
            "content" => $content
        ];

        return $messages;
    }

    /**
     * Returns a formatted collection of the conversation thus far (without forgotten messages)
     * @return mixed[]
     */
    private function preparePreviousConversation(){
        $messages = $this->messages()->orderBy('created_at', 'asc')->get();
        return $messages->map(function ($message) {
            return [
                'role' => $message->is_from_user ? 'user' : 'assistant',
                'content' => $message->content
            ];
        })->toArray();
    }

    protected $table = 'open_ai_conversations';

}