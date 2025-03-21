<?php

return array(
	'ai_marker' => array(
		'openai_api_key' => 'OpenAI API Key',
		'openai_api_key_help' => 'Your OpenAI API key for accessing the GPT models.',
		'openai_proxy_url' => 'OpenAI Proxy URL (Optional)',
		'openai_proxy_url_help' => 'Optional proxy URL for OpenAI API requests. Leave empty for direct access.',
		'openai_model' => 'LLM Model',
		'openai_model_help' => 'Enter the OpenAI model name (e.g., gpt-3.5-turbo, gpt-4, gpt-4-turbo). You can use any model supported by OpenAI API.',
		'system_prompt' => 'System Prompt',
		'system_prompt_help' => 'Instructions for the AI on how to determine if an article is worth reading. The response should be a JSON with "evaluation" field containing either "USEFUL" or "USELESS".',
		'default_system_prompt' => 'You are an assistant helping to filter news articles. Analyze the content and determine if it is worth reading based on information value, depth, and relevance. Reply with a JSON object containing an "evaluation" field with value "USEFUL" for high-quality, informative content or "USELESS" for low-quality, superficial, or promotional content. Example: {"evaluation": "USEFUL", "reason": "This article provides valuable information."} or {"evaluation": "USELESS", "reason": "This article is promotional with little information value."}',
		'article_marked_read' => 'AI has marked article as read: not worth reading',
		'error_missing_api_key' => 'Missing OpenAI API key in configuration',
		'error_api_request' => 'Error making request to OpenAI API'
	),
);
