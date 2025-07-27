<?php

class AiMarkerExtension extends Minz_Extension {
	private $default_system_prompt;

	// 定义配置默认值常量
	const DEFAULT_MODEL = 'gpt-3.5-turbo';

	const DEFAULT_THREAD_SCORE = 3.0;

	public function init() {
		$this->registerTranslates();
		
		// 获取默认系统提示词
		$this->default_system_prompt = _t('ext.ai_marker.default_system_prompt');
		
		// 注册钩子，在文章插入前进行处理
		$this->registerHook('entry_before_insert', array($this, 'processArticleHook'));
	}
	
	public function handleConfigureAction() {
		$this->registerTranslates();
		
		if (Minz_Request::isPost()) {
			FreshRSS_Context::$user_conf->openai_api_key = Minz_Request::param('openai_api_key', '');
			FreshRSS_Context::$user_conf->openai_proxy_url = Minz_Request::param('openai_proxy_url', '');
			FreshRSS_Context::$user_conf->openai_model = Minz_Request::param('openai_model', self::DEFAULT_MODEL);
			FreshRSS_Context::$user_conf->system_prompt = Minz_Request::param('system_prompt', $this->default_system_prompt);
			FreshRSS_Context::$user_conf->thread_score = Minz_Request::param('thread_score', self::DEFAULT_THREAD_SCORE);
			FreshRSS_Context::$user_conf->save();
			
			Minz_Request::good(_t('feedback.conf.updated'), array(
				'params' => array('config' => 'display')
			));
		}
	}

	
	public function processArticleHook($entry) {

		// 如果文章是更新而非新发布，则跳过处理, 参考https://github.com/FreshRSS/FreshRSS/discussions/5602
		if ($entry->id() == null) {
			//updated article not process
			return $entry;
		} 

		// 存储文档原本的hash值，注：因为文档会根据title和内容作者进行hash排重，下面会修改相关的信息，所以预先保存
		$originalHash = $entry->hash();

		// 已读文章不需要处理
		if ($entry->isRead()) {
			return $entry;
		}
		
		// 获取文章标题和内容
		$title = $entry->title();
		$content = $entry->content();
		
		// 调用LLM进行判断
		$result = $this->askLLM($title, $content);
		
		// 如果有返回JSON数据
		if (is_array($result)) {


			$thread_score = FreshRSS_Context::$user_conf->thread_score ?? self::DEFAULT_THREAD_SCORE;

			// 添加翻译标题（如果有且原标题不是中文）
			if (!empty($result['translated_title']) && !$this->containsChinese($title)) {
			    $entry->_title($result['translated_title']);
			}

			// 准备AI分析内容的内部HTML
			$aiContentHtml = '';

			// 添加评分和理由
			if (isset($result['quality_score']) && is_numeric($result['quality_score'])) {
				$score = (float)$result['quality_score'];
				$scoreColor = $score < (float)$thread_score ? '#e53935' : '#4caf50';

				$aiContentHtml .= '<div style="padding: 5px 0;">';
				$aiContentHtml .= '<strong>[评分]：</strong><span style="color: ' . $scoreColor . '; font-weight: bold;">' . $score . '</span>';

				if (!empty($result['evaluation_reason'])) {
					$aiContentHtml .= '<br><strong>[理由]：</strong>' . $this->renderSimpleMarkdown($result['evaluation_reason']);
				}
				$aiContentHtml .= '</div>';
			}

			// 添加文章摘要
			if (!empty($result['abstract'])) {
				if (!empty($aiContentHtml)) {
					$aiContentHtml .= '<hr style="border: none; border-top: 1px dashed #ccc; margin: 8px 0;">';
				}
				$aiContentHtml .= '<div style="padding: 5px 0;"><strong>[摘要]：</strong><br>' . $this->renderSimpleMarkdown($result['abstract']) . '</div>';
			}

			// 如果有AI生成的内容，则用可折叠框包裹并添加到内容前
			if (!empty($aiContentHtml)) {
				$finalHtml = '
					<details open style="border: 1px solid #e0e0e0; border-radius: 8px; margin-bottom: 1.5em; background-color: #fdfdfd; font-size: 14px; line-height: 1.6;">
						<summary style="padding: 12px; font-weight: bold; cursor: pointer; outline: none; display: list-item;">
							🤖 AI 智能分析 (点击展开/折叠)
						</summary>
						<div style="padding: 0 15px 15px; border-top: 1px solid #e0e0e0;">
							' . $aiContentHtml . '
						</div>
					</details>
				';
				$entry->_content($finalHtml . $content);
			}
			
			// 准备要添加的标签
			$aiTags = $result['tags'] ?? [];
			if (!is_array($aiTags)) {
				$aiTags = []; // 安全检查
			}

			// 如果有评分，则添加评分标签
			if (isset($result['quality_score']) && is_numeric($result['quality_score'])) {
				$scoreTag = 'AI评分: ' . round((float)$result['quality_score']);
				$aiTags[] = $scoreTag;
			}

			// 如果有任何AI生成的标签（包括评分标签），则进行合并和设置
			if (!empty($aiTags)) {
				$currentTags = $entry->tags();
				
				// 确保所有标签都是字符串
				foreach ($aiTags as &$tag) {
					$tag = (string)$tag;
				}
				
				// 合并所有标签并去重
				$allTags = array_unique(array_merge($currentTags, $aiTags));
				
				// 设置文章标签
				$entry->_tags($allTags);
			}
			
			// 判断文章是否值得阅读
			$isWorthReading = true;
			
			// 检查quality_score是否大于thread_score
			if (isset($result['quality_score']) && is_numeric($result['quality_score']) && (float)$result['quality_score'] < (float)$thread_score) {
				$isWorthReading = false;
				Minz_Log::debug("文章得分:" . $result['quality_score'] . "，非必读文章: " . $title);
			}
			
			// 如果非必读文章，标记为已读
			if (!$isWorthReading) {
				$entry->_isRead(true);
			}
		} 

		$entry->_hash($originalHash);
		
		return $entry;
	}
	
	private function askLLM($title, $content) {
		// 从用户配置中获取API密钥
		$api_key = FreshRSS_Context::$user_conf->openai_api_key ?? '';
		if (empty($api_key)) {
			Minz_Log::error(_t('ext.ai_marker.error_missing_api_key'));
			return 'USEFUL'; // 默认认为有用，以避免错误地过滤内容
		}
		
		// 获取其他配置
		$proxy_url = FreshRSS_Context::$user_conf->openai_proxy_url ?? '';
		$model = FreshRSS_Context::$user_conf->openai_model ?? self::DEFAULT_MODEL;
		$system_prompt = FreshRSS_Context::$user_conf->system_prompt ?? $this->default_system_prompt;
		
		// 清理内容，移除HTML标签和实体编码
		$cleanedContent = $this->cleanContent($content);
		
		// 准备向OpenAI API发送请求
		$api_url = !empty($proxy_url) ? $proxy_url : 'https://api.openai.com/v1/chat/completions';
		
		// 准备请求数据
		$data = array(
			'model' => $model,
			'messages' => array(
				array(
					'role' => 'system',
					'content' => $system_prompt
				),
				array(
					'role' => 'user',
					'content' => "标题: $title\n\n内容: $cleanedContent"
				)
			),
			'temperature' => 0.1 // 低温度以获得更确定的回答
		);
		
		// 发送请求
		$response = $this->sendRequest($api_url, $data, $api_key);
		
		// 解析响应
		if ($response) {
			$json = json_decode($response, true);
			if (isset($json['choices'][0]['message']['content'])) {
				$content = $json['choices'][0]['message']['content'];
				
				// 尝试提取JSON部分
				if (preg_match('/\{.*\}/s', $content, $matches)) {
					$jsonStr = $matches[0];
					$contentJson = json_decode($jsonStr, true);
					
					// 检查是否成功解析为JSON
					if ($contentJson) {
						// 检查是否包含新格式的字段
						if (isset($contentJson['reading_recommendation']) || 
						    isset($contentJson['abstract']) || 
						    isset($contentJson['translated_title'])) {
							return $contentJson;
						}
						
						// 向下兼容旧格式
						if (isset($contentJson['evaluation'])) {
							$value = strtoupper(trim($contentJson['evaluation']));
							if ($value === 'USELESS') {
								return 'USELESS';
							} elseif ($value === 'USEFUL') {
								return 'USEFUL';
							}
						}
					}
				}
				
				// 如果无法从JSON提取结果，回退到文本匹配
				if (stripos($content, 'USELESS') !== false) {
					return 'USELESS';
				}
			}
		}
		
		// 默认返回USEFUL
		return 'USEFUL';
	}
	
	/**
	 * 清理HTML内容，转换为纯文本
	 * 
	 * @param string $content HTML内容
	 * @return string 清理后的纯文本
	 */
	private function cleanContent($content) {
		// 解码HTML实体
		$decoded = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		
		// 移除HTML标签，保留其文本内容
		$textOnly = strip_tags($decoded);
		
		// 移除多余的空白
		$cleaned = preg_replace('/\s+/', ' ', $textOnly);
		
		// 记录内容长度变化，用于调试
		Minz_Log::debug('内容清理: 原始长度 ' . strlen($content) . ' -> 清理后长度 ' . strlen($cleaned));
		
		return trim($cleaned);
	}
	
	/**
	 * 检测字符串是否包含中文
	 * 
	 * @param string $str 要检测的字符串
	 * @return boolean 如果包含中文返回true，否则返回false
	 */
	private function containsChinese($str) {
		return preg_match('/[\x{4e00}-\x{9fa5}]/u', $str) > 0;
	}
	
	private function sendRequest($url, $data, $api_key) {
		$ch = curl_init($url);
		
		$headers = array(
			'Content-Type: application/json',
			'Authorization: Bearer ' . $api_key
		);
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		
		$response = curl_exec($ch);
		
		if (curl_errno($ch)) {
			Minz_Log::error(_t('ext.ai_marker.error_api_request') . ': ' . curl_error($ch));
			return false;
		}
		
		curl_close($ch);
		return $response;
	}

	private function renderSimpleMarkdown($text) {
		// 1. Convert **bold** to <strong>bold</strong>
		$html = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $text);
	
		// 2. Convert --- to <hr> with less margin
		$html = str_replace('---', '<hr style="border: none; border-top: 1px solid #eee; margin: 0.8em 0;">', $html);
	
		// 3. Convert newlines to <br>
		$html = nl2br($html, false);
	
		return $html;
	}
}
