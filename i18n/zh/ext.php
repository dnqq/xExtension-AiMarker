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
		'system_prompt_help' => '指导AI如何分析文章并生成包含评估、摘要和翻译标题的JSON响应。',
		'default_system_prompt' => '你是一个帮助分析新闻和文章的助手。请分析内容并根据信息价值、深度和相关性进行评价。回复必须是JSON对象格式，包含以下字段：
1. translated_title：如果原文标题不是中文，提供中文翻译；否则留空
2. tags：提取3-6个关键词标签
3. abstract：用中文总结文章的主要内容（100-200字）
4. evaluation_reason：评价该文章的价值和为什么值得/不值得阅读
5. quality_score：0-5分评分，表示文章质量
6. reading_recommendation：必须是以下选项之一："非常值得阅读"、"值得阅读"、"一般，可以阅读"、"不太值得阅读"、"不值得阅读"

JSON示例：
{
  "translated_title": "人工智能在医疗领域的应用进展",
  "tags": ["AI", "医疗", "技术", "创新", "健康"],
  "abstract": "本文分析了人工智能在医疗领域的最新应用，包括疾病诊断、药物研发和个性化治疗方案。文章指出AI技术已显著提高诊断准确率，并加速了新药研发过程。",
  "evaluation_reason": "该文章提供了AI医疗应用的全面概述，数据丰富，观点新颖，对于了解医疗技术发展趋势很有价值。",
  "quality_score": 4.5,
  "reading_recommendation": "非常值得阅读"
}',
		'article_marked_read' => 'AI已将文章标记为已读：内容不值得阅读',
		'article_marked_useless' => 'AI已在文章标题前添加[USELESS]前缀：内容不值得阅读',
		'article_marked_useful' => 'AI已在文章标题前添加[USEFUL]前缀：内容值得阅读',
		'error_missing_api_key' => '配置中缺少OpenAI API密钥',
		'error_api_request' => '请求OpenAI API时出错'
	),
); 