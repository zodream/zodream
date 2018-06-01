<?php 
namespace Zodream\Infrastructure\Mailer;

/**
* mail
* 
* @author Jason
* @time 2015-11-29
*/
use Zodream\Infrastructure\Base\ConfigObject;

abstract class BaseMailer extends ConfigObject {

	protected $mail;

    protected $configKey = 'mail';

	/**
	 * 设置发送者的信息
	 * @param string $username
	 * @param string $password
	 * @return $this
	 */
	abstract public function setUser($username, $password);

	/**
	 * 设置发件人
	 * @param string $address
	 * @param string $name
	 * @param bool|string $auto
	 * @return $this
	 */
	abstract public function setFrom($address, $name = '', $auto = TRUE);

	/**
	 * 添加接收者
	 * @param string $address
	 * @param string $name
	 * @return $this
	 */
	abstract public function addAddress($address, $name = '');

	/**
	 * 添加转发
	 * @param string $address
	 * @param string $name
	 * @return $this
	 */
	public function addReplyTo($address, $name = '') {
		return $this;
	}

	/**
	 * 添加抄送
	 * @param string $address
	 * @param string $name
	 * @return $this
	 */
	public function addCC($address, $name = '') {
		return $this;
	}

	/**
	 * 添加
	 * @param string $address
	 * @param string $name
	 * @return $this
	 */
	public function addBCC($address, $name = '') {
		return $this;
	}

	/**
	 * 添加附件
	 * @param string $file
	 * @param string $name
	 * @return $this
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
	abstract public function isHtml($isHtml = TRUE);

	/**
	 * 发送
	 * @param string $subject
	 * @param string $body
	 * @param string $altBody
	 * @return bool
	 */
	abstract public function send($subject, $body, $altBody = '');
	
	/**
	 * 获取错误信息
	 */
	abstract public function getError();
}