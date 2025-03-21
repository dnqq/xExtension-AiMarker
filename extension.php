<?php

class AiMarkerExtension extends Minz_Extension {
	private $default_system_prompt;

	public function init() {
		$this->registerTranslates();
		
		// 获取默认系统提示词
		$this->default_system_prompt = _t('ext.ai_marker.default_system_prompt');
		
		// 注册钩子，在文章显示前进行处理
		$this->registerHook('entry_before_display', array($this, 'processArticleHook'));
		
		// 预处理配置
		$this->loadConfigValues();
	}
	
	public function handleConfigureAction() {
		$this->registerTranslates();
		
		if (Minz_Request::isPost()) {
			$this->saveConfiguration(
				Minz_Request::param('openai_api_key', ''),
				Minz_Request::param('openai_proxy_url', ''),
				Minz_Request::param('openai_model', 'gpt-3.5-turbo'),
				Minz_Request::param('system_prompt', $this->default_system_prompt)
			);
			
			Minz_Request::good(_t('feedback.conf.updated'), array(
				'params' => array('config' => 'display')
			));
		}
		
		// 将配置值传递给视图
		$this->loadConfigValues();
	}
	
	private function loadConfigValues() {
		$this->openai_api_key = $this->getConfigValue('openai_api_key', '');
		$this->openai_proxy_url = $this->getConfigValue('openai_proxy_url', '');
		$this->openai_model = $this->getConfigValue('openai_model', 'gpt-3.5-turbo');
		$this->system_prompt = $this->getConfigValue('system_prompt', $this->default_system_prompt);
	}
	
	private function saveConfiguration($api_key, $proxy_url, $model, $system_prompt) {
		$this->setConfigValue('openai_api_key', $api_key);
		$this->setConfigValue('openai_proxy_url', $proxy_url);
		$this->setConfigValue('openai_model', $model);
		$this->setConfigValue('system_prompt', $system_prompt);
	}

	public function processArticleHook($entry) {
		if ($entry->isRead()) {
			// 文章已经被标记为已读，不需要进一步处理
			return $entry;
		}
		
		// 获取文章内容
		$title = $entry->title();
		$content = $entry->content();
		
		// 调用LLM进行判断
		$result = $this->askLLM($title, $content);
		
		if ($result === 'USELESS') {
			// 如果LLM判断文章无用，将其标记为已读
			$entry->_isRead(true);
			Minz_Log::debug(_t('ext.ai_marker.article_marked_read') . ': ' . $title);
		}
		
		return $entry;
	}
	
	private function askLLM($title, $content) {
		$api_key = $this->getConfigValue('openai_api_key', '');
		if (empty($api_key)) {
			Minz_Log::error(_t('ext.ai_marker.error_missing_api_key'));
			return 'USEFUL'; // 默认认为有用，以避免错误地过滤内容
		}
		
		$proxy_url = $this->getConfigValue('openai_proxy_url', '');
		$model = $this->getConfigValue('openai_model', 'gpt-3.5-turbo');
		$system_prompt = $this->getConfigValue('system_prompt', $this->default_system_prompt);
		
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
					'content' => "标题: $title\n\n内容: $content"
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
					
					// 检查JSON中是否包含evaluation字段
					if ($contentJson && isset($contentJson['evaluation'])) {
						$value = strtoupper(trim($contentJson['evaluation']));
						if ($value === 'USELESS') {
							return 'USELESS';
						} elseif ($value === 'USEFUL') {
							return 'USEFUL';
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
}
