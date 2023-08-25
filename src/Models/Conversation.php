<?php

namespace Lanos\OpenAiConversations\Models;

use Exception;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Lanos\OpenAiConversations\Enums\OpenAiRole;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Lanos\OpenAiConversations\Concerns\GPTModelData;
use Lanos\OpenAiConversations\Concerns\HasOrderedUUID;

class Conversation extends Model
{
    use HasOrderedUUID,
        SoftDeletes,
        GPTModelData;

    protected $fillable = [
        'model',
        'n_value',
        'token_limit',
    ];

    public function getTable()
    {
        return config('open_ai_conversations.database.conversations.table');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            // APPLY DEFAULT N
            $model->n_value = config('open_ai_conversations.default_n_value');
            // APPLY DEFAULT MODEL
            $model->model = config('open_ai_conversations.default_model');

            // APPLY DEFAULT TOKEN LIMIT
            $maxTokenLimit = self::assertTokenLimit(config('open_ai_conversations.default_model'));

            if (config('open_ai_conversations.default_conversation_token_limit') === 'MAX') {
                $model->token_limit = $maxTokenLimit;
            }

            // IN CASE DEVELOPER TRIES TO CONFIGURE HIGHER TOKEN LIMIT THAN IS ALLOWED
            if (config('open_ai_conversations.default_conversation_token_limit') !== 'MAX' && config('open_ai_conversations.default_conversation_token_limit') > $maxTokenLimit) {
                throw new \Exception('Your token limit is too high for the chosen GPT model');
            }
        });
    }

    public function messages()
    {
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
     * @param $content
     * @param $returnQuestion
     * @return array|Model|HasMany|object|null
     * @throws Exception
     */
    public function askQuestion($content, $returnQuestion = false)
    {
        $estimatedTokenLength = ConversationMessage::estimateTokenRequestLength($content);

        // MAKE SURE THERE WILL BE ENOUGH TOKENS AVAILABLE FOR THIS, IF NOT, FORGET SOME MESSAGES
        $this->enforceTokenLimit($estimatedTokenLength);

        // OK, NOW WE GOT THE LIMIT COVERED, LET'S PREPARE THE REQUEST
        $payload = $this->prepareMessagePayload($content);

        $commitRequest = OpenAI::chat()->create([
            'model'    => $this->model,
            'messages' => $payload,
        ]);

        if (isset($commitRequest['choices'])) {

            // APPEND THE QUESTION
            $question = $this->messages()->create([
                "estimated_token_length" => $estimatedTokenLength,
                "actual_token_length"    => $commitRequest['usage']['prompt_tokens'],
                'role'                   => OpenAiRole::USER,
                "content"                => $content
            ]);


            $this->messages()->create([
                "estimated_token_length" => 0,
                "actual_token_length"    => $commitRequest['usage']['completion_tokens'],
                'role'                   => OpenAiRole::ASSISTANT,
                "content"                => $commitRequest['choices'][0]['message']['content']
            ]);

            if ($returnQuestion === false) {
                return $this->messages()->orderBy('id', 'desc')->first();
            } else {
                return [
                    "question" => $question,
                    "answer" => $this->messages()->orderBy('id', 'desc')->first()
                ];
            }

            // RETURN THE NEW MESSAGE

        } else {
            throw new Exception('GPT Request Failed');
        }
    }


    /**
     * Appends a new message to the formatted conversation ready to send to OpenAI
     * @param $content
     * @return mixed[]
     */
    private function prepareMessagePayload($content)
    {
        $messages = $this->preparePreviousConversation();

        $messages[] = [
            "role"    => OpenAiRole::USER,
            "content" => $content
        ];

        return $messages;
    }

    /**
     * Returns a formatted collection of the conversation thus far (without forgotten messages)
     * @return mixed[]
     */
    private function preparePreviousConversation()
    {
        $messages = $this->messages()->orderBy('created_at', 'asc')->get();

        return $messages->map(function ($message) {
            return [
                'role'    => $message->role,
                'content' => $message->content
            ];
        })->toArray();
    }
}
