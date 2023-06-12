<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Mailer;

/**
* mail
* 
* @author Jason
* @time 2015-11-29
*/
use Zodream\Infrastructure\Base\ConfigObject;

abstract class BaseMailer extends ConfigObject {

    protected string $configKey = 'thirdparty.mail';

	/**
	 * 设置发送者的信息
	 * @param string $username
	 * @param string $password
	 * @return $this
	 */
	abstract public function setUser(string $username, string $password);

	/**
	 * 设置发件人
	 * @param string $address
	 * @param string $name
	 * @param bool|string $auto
	 * @return $this
	 */
	abstract public function setFrom(string $address, string $name = '', bool $auto = true);

	/**
	 * 添加接收者
	 * @param string $address
	 * @param string $name
	 * @return $this
	 */
	abstract public function addAddress(string $address, string $name = '');

	/**
	 * 添加转发
	 * @param string $address
	 * @param string $name
	 * @return $this
	 */
	public function addReplyTo(string $address, string $name = '') {
		return $this;
	}

	/**
	 * 添加抄送
	 * @param string $address
	 * @param string $name
	 * @return $this
	 */
	public function addCC(string $address, string $name = '') {
		return $this;
	}

	/**
	 * 添加
	 * @param string $address
	 * @param string $name
	 * @return $this
	 */
	public function addBCC(string $address, string $name = '') {
		return $this;
	}

	/**
	 * 添加附件
	 * @param string $file
	 * @param string $name
	 * @return $this
	 */
	public function addAttachment(string $file, string $name = '') {
		return $this;
	}

	/**
	 * 发送的内容是否是html 或 plain
	 * @param bool $isHtml
	 * @return $this
	 */
	abstract public function isHtml(bool $isHtml = true);

	/**
	 * 发送
	 * @param string $subject
	 * @param string $body
	 * @param string $altBody
	 * @return bool
	 */
	abstract public function send(string $subject, string $body, string $altBody = ''): bool;
	
	/**
	 * 获取错误信息
	 */
	abstract public function getError(): string;
}