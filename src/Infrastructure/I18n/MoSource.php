<?php 
namespace Zodream\Infrastructure\I18n;
/**
* 语言及语言包类
* 
* @author Jason
*/

class MoSource extends I18n {
	
	public function setLanguage($arg = null) {
		parent::setLanguage($arg);
		switch (strtolower($this->language)) {
			case 'en':
				$this->set('lang', 'en_US.UTF8');
				break;
			case 'en_us':
				$this->set('lang', 'en_US.UTF8');
				break;
			case 'en_gb':
				$this->set('lang', 'en_US.UTF8');
				break;
			case 'zh_cn':
			default:
				$this->set('lang', 'zh_CN.UTF8');
				break;
		}
	}

	public function reset() {
		putenv('LANG='.$this->get('lang'));
		putenv('LANGUAGE='.$this->get('lang'));
		setlocale(LC_ALL, $this->get('lang'));
		bindtextdomain($this->fileName, (string)$this->directory);
		textdomain($this->fileName);
		bind_textdomain_codeset($this->fileName, 'UTF-8');
	}

	public function translate($message, $param = [], $name = null) {
        if (empty($message)) {
            return $message;
        }
		parent::translate($message, $param, $name);
		return $this->format(gettext($message), $param);
	}
}