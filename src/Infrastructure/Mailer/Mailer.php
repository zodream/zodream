<?php 
namespace Zodream\Infrastructure\Mailer;

/**
* mail
* 
* @author Jason
* @time 2015-11-29
*/

use PHPMailer\PHPMailer\SMTP;
use Zodream\Infrastructure\Support\Template;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer extends BaseMailer {

    protected array $configs = [
        'secure' => PHPMailer::ENCRYPTION_STARTTLS
    ];
	/**
	 * @var PHPMailer
	 */
	protected $mail;
	
	public function __construct($config = array()) {
        $this->loadConfigs($config);
		$this->mail          = new PHPMailer();
		$this->mail->CharSet = PHPMailer::CHARSET_UTF8;
		$this->mail->isSMTP();
		$this->mail->SMTPAuth = true;
		$this->mail->SMTPSecure = $this->configs['secure'];
		if (app()->isDebug()) {
			$this->mail->SMTPDebug = SMTP::DEBUG_SERVER;
		}
		$email = empty($this->configs['email']) ? $this->configs['user'] : $this->configs['email'];
		$name = empty($this->configs['name']) ? $email : $this->configs['name'];
		$this->setHost($this->configs['host'], $this->configs['port'])
			->setUser($this->configs['user'], $this->configs['password'])
			->setFrom($email, $name);
	}

	/**
	 * 设置发送者的信息
	 * @param string $username
	 * @param string $password
	 * @return $this
	 */
	public function setUser($username, $password) {
		$this->mail->Username = $username;
		$this->mail->Password = $password;
		return $this;
	}

	/**
	 * 设置host
	 * @param string $host
	 * @param string $port
	 * @return $this
	 */
	public function setHost($host, $port) {
		$this->mail->Host = $host;
		$this->mail->Port = $port;
		return $this;
	}

    /**
     * 设置发件人
     * @param string $address
     * @param string $name
     * @param bool|string $auto
     * @return $this
     * @throws Exception
     */
	public function setFrom($address, $name = '', $auto = TRUE) {
		$this->mail->setFrom($address, $name, $auto);
		return $this;
	}

    /**
     * 添加接收者
     * @param string $address
     * @param string $name
     * @return $this
     * @throws Exception
     */
	public function addAddress($address, $name = '') {
		$this->mail->addAddress($address, $name);
		return $this;
	}

    /**
     * 添加转发
     * @param string $address
     * @param string $name
     * @return $this
     * @throws Exception
     */
	public function addReplyTo($address, $name = '') {
		$this->mail->addReplyTo($address, $name);
		return $this;
	}

    /**
     * 添加抄送
     * @param string $address
     * @param string $name
     * @return $this
     * @throws Exception
     */
	public function addCC($address, $name = '') {
		$this->mail->addCC($address, $name);
		return $this;
	}

    /**
     * 添加
     * @param string $address
     * @param string $name
     * @return $this
     * @throws Exception
     */
	public function addBCC($address, $name = '') {
		$this->mail->addBCC($address, $name);
		return $this;
	}

    /**
     * 添加附件
     * @param string $file
     * @param string $name
     * @return $this
     * @throws Exception
     */
	public function addAttachment($file, $name = '') {
		$this->mail->addAttachment($file, $name);
		return $this;
	}

	/**
	 * 发送的内容是否是html 或 plain
	 * @param bool $isHtml
	 * @return $this
	 */
	public function isHtml($isHtml = TRUE) {
		$this->mail->isHtml($isHtml);
		return $this;
	}

    /**
     * 发送
     * @param string $subject
     * @param string $body
     * @param string $altBody
     * @return bool
     * @throws Exception
     */
	public function send($subject, $body, $altBody = '') {
		$this->mail->Subject = $subject;
		$this->mail->Body    = $body;
		$this->mail->AltBody = $altBody;
		return $this->mail->send();
	}

	public function sendTemplate($file, array $args, $subject) {
	    $template = new Template();
        $template->set($args);
        if (is_dir($this->configs['template'])) {
            return $this->send($subject, $template->getText($file));
        }
        return $this->send($subject,
            $template->replaceByArray($this->configs['template'],
                $template->get()));
    }
	
	/**
	 * 获取错误信息
	 */
	public function getError() {
		return $this->mail->ErrorInfo;
	}
	
	public function __set($name, $value) {
		if (isset($this->mail->$name)) {
			$this->mail->$name = $value;
		}
	}

}