<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\I18n;
/**
* 语言及语言包类
* 
* @author Jason
*/

class MoSource extends I18n {


    protected function formatLanguage(string $language): string {
        return match (str_replace('-', '_', strtolower($language))) {
            'en', 'en_us', 'en_gb' => 'en_US',
            default => 'zh_CN',
        };
    }

    public function reset(): void {
        $langEncoding = $this->language.'.UTF8';
		putenv('LANG='.$langEncoding);
		putenv('LANGUAGE='.$langEncoding);
		setlocale(LC_ALL, $langEncoding);
		bindtextdomain($this->fileName, (string)$this->directory);
		textdomain($this->fileName);
		bind_textdomain_codeset($this->fileName, 'UTF-8');
	}

	public function translate(mixed $message, array $param = [], ?string $name = null): mixed {
        if (empty($message)) {
            return $message;
        }
		$this->resetFileIfNotEmpty($name);
		return $this->format(gettext((string)$message), $param);
	}
}