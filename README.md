# xExtension-AiMarker

一个FreshRSS扩展，使用人工智能(LLM)来智能过滤文章。

## 功能

- 使用OpenAI API自动判断文章是否值得阅读
- 可自定义系统提示词来指导AI的判断标准
- 支持配置OpenAI API密钥和代理URL
- 自动将无用的文章标记为已读，减少信息过载

## 使用方法

1. 在FreshRSS的扩展管理页面中启用AiMarker扩展
2. 配置您的OpenAI API密钥和代理URL(如需)
3. 自定义系统提示词以符合您的过滤标准
4. 正常使用FreshRSS，插件会自动过滤新文章

## 注意事项

- 需要有效的OpenAI API密钥
- 处理大量文章可能会消耗API配额
- 系统提示词的质量会直接影响过滤效果
