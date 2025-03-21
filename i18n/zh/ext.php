<?php

return array(
	'ai_marker' => array(
		'openai_api_key' => 'OpenAI API密钥',
		'openai_api_key_help' => '您的OpenAI API密钥，用于访问GPT模型。',
		'openai_proxy_url' => 'OpenAI代理URL（可选）',
		'openai_proxy_url_help' => '可选的OpenAI API请求代理URL。如不需要，请留空。',
		'openai_model' => '语言模型',
		'openai_model_help' => '输入OpenAI模型名称（例如：gpt-3.5-turbo、gpt-4、gpt-4-turbo）。您可以使用任何OpenAI API支持的模型。',
		'system_prompt' => '系统提示词',
		'system_prompt_help' => '指导AI如何判断文章是否值得阅读的提示词。回应应为JSON格式，包含"evaluation"字段，其值为"USEFUL"或"USELESS"。',
		'default_system_prompt' => '你是一个帮助过滤新闻文章的助手。请分析内容并根据信息价值、深度和相关性判断是否值得阅读。以JSON对象格式回应，包含"evaluation"字段，值为"USEFUL"（对于高质量、有信息量的内容）或"USELESS"（对于低质量、肤浅或宣传性内容）。示例：{"evaluation": "USEFUL", "reason": "这篇文章提供了有价值的信息。"}或{"evaluation": "USELESS", "reason": "这篇文章主要是宣传内容，信息价值有限。"}',
		'article_marked_read' => 'AI已将文章标记为已读：内容不值得阅读',
		'error_missing_api_key' => '配置中缺少OpenAI API密钥',
		'error_api_request' => '请求OpenAI API时出错'
	),
); 