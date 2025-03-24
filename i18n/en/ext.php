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
		'system_prompt_help' => 'Instructions for the AI on how to analyze articles and generate a JSON response with evaluation, summary, and translated title.',
		'thread_score' => 'Thread Score for Article',
		'thread_score_help' => 'The score for the article, score lower than it will be marked as read',
		'default_system_prompt' => 'You are an assistant helping to analyze news articles and content. Analyze the content based on information value, depth, and relevance. Reply with a JSON object containing the following fields:
1. translated_title: Provide a Chinese translation of the title if the original is not in Chinese; otherwise leave it empty
2. tags: Extract 3-6 keyword tags
3. abstract: Summarize the main content in Chinese (100-200 characters)
4. evaluation_reason: Evaluate the article\'s value and why it is worth/not worth reading
5. quality_score: A rating from 0-5 points representing article quality
6. reading_recommendation: Must be one of: "非常值得阅读" (highly recommended), "值得阅读" (recommended), "一般，可以阅读" (average, readable), "不太值得阅读" (not very recommended), "不值得阅读" (not recommended)

JSON Example:
{
  "translated_title": "人工智能在医疗领域的应用进展",
  "tags": ["AI", "Healthcare", "Technology", "Innovation", "Health"],
  "abstract": "本文分析了人工智能在医疗领域的最新应用，包括疾病诊断、药物研发和个性化治疗方案。文章指出AI技术已显著提高诊断准确率，并加速了新药研发过程。",
  "evaluation_reason": "This article provides a comprehensive overview of AI medical applications with rich data and novel perspectives, valuable for understanding trends in medical technology.",
  "quality_score": 4.5,
  "reading_recommendation": "非常值得阅读"
}',
		'article_marked_read' => 'AI has marked article as read: not worth reading',
		'article_marked_useless' => 'AI has marked article title with [USELESS] prefix: not worth reading',
		'article_marked_useful' => 'AI has marked article title with [USEFUL] prefix: worth reading',
		'error_missing_api_key' => 'Missing OpenAI API key in configuration',
		'error_api_request' => 'Error making request to OpenAI API'
	),
);
