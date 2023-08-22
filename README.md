<h1 align=center>
	Laravel GPT Conversations 
</h1>

## Intro

This package is built on top of the [openai-php/laravel](https://github.com/openai-php/laravel) package to allow you to build conversation sessions where context is preserved using a database.

This package is just an extra layer on top of the amazing package developed by:

- Nuno Maduro: **[github.com/sponsors/nunomaduro](https://github.com/sponsors/nunomaduro)**
- Sandro Gehri: **[github.com/sponsors/gehrisandro](https://github.com/sponsors/gehrisandro)**

The openai-php/laravel is required and should be installed as part of this install.

### Setup

> **Requires [PHP 8.1+](https://php.net/releases/)**

Really simple. Install our package (if you don't have the openai-php/laravel installed, composer should try to install it)

```bash
composer require lanos/laravel-open-ai-conversations
```

Then you need to just run the migrations

```bash
php artisan migrate
```

If you haven't set up the OpenAI-PHP Laravel package you can publish their config like so:

```bash
php artisan vendor:publish --provider="OpenAI\Laravel\ServiceProvider"
```

Then add the environment variables as needed.

```env
OPENAI_API_KEY=sk-...
```

You can also override some of the default config variables in the .env for my plugin, such as setting the default model for a conversation.

```env
OPENAI_DEFAULT_MODEL=gpt-4-32k
OPENAI_TOKEN_LIMIT=4096
```

### Examples
Once you have fully configured both plugins you simply create a conversation using the eloquent interface. 

The defaults are filled in for you, but you can override upon creation.

Once you have created the conversation you can ask questions. The plugin will automatically append all previous responses so that the model has consciousness of previous messages in the conversation. It also automatically ensures no token limits are hit by "forgetting" older messages as needed.

```
<?php
  
  // CREATES THE CONVERSATION
  $conversation = Conversation::create();
  
  // ASK A NEW QUESTION
  $capitalCity = $conversation->askQuestion('What is the capital city of England?');
  
  // ASK A FOLLOW UP QUESTION
  $population = $conversation->askQuestion('And what is the population of that city?');
  
  // GETS ALL OF THE QUESTIONS AND ANSWERS UP UNTIL NOW
  $messages = $conversation->messages()->get();
  
```

## Forgotten messages

Due to token limits, when necessary the plugin will soft delete older messages, similar to how chat GPT does it. The difference is with this, it will do it less often, as the token limits are higher on the API depending on what model you use. Be wary that requests can become expensive.

You can use the withTrashed function on eloquent to get all the forgotten messages.  

```
  // GETS ALL OF THE QUESTIONS AND ANSWERS UP UNTIL NOW INCLUJDING FOROGTTEN ONES
  $messages = $conversation->messages()->withTrashed()->get();
  
  // GETS ONLY FORGOTTEN MESSAGES
  $messages = $conversation->messages()->onlyTrashed()->get();
```

## License

Please refer to the license.md in this repository.