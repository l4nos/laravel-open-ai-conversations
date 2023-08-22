<?php

return [

    // MAX or numerical value (must be less than the default models max token limit)
    'default_conversation_token_limit' => env('OPEN_AI_TOKEN_LIMIT', 'MAX'),
    // DEFAULT NUMBER OF RESPONSES FROM OPENAI
    'default_n_value' => env('OPENAI_N_VALUE', 1),
    // DEFAULT MODEL OF CHOICE WHEN STARTING A NEW CONVERSATION SESSION
    'default_model' => env('OPENAI_DEFAULT_MODEL', 'gpt-4'),
    'override_models' => env('OPENAI_MODEL_LIMITS')

];