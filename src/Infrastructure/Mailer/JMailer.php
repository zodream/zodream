<?php
namespace Zodream\Infrastructure\Mailer;

use Exception;
use COM;

class JMailer extends BaseMailer {

    /**
     * @var object
     */
    protected $mail;

    public function __construct() {
        $this->mail = new COM('JMail.Message');
        if (!$this->mail) {
            throw new Exception(
                __('Cannot use JMail DLL!')
            );
        }
        if (defined('DEBUG') && DEBUG) {
            $this->mail->SiLent = true; //设置成True的话Jmail不会提示错误只会返回True和False
        }
        $this->mail->LogGing = true;   //是否开启日志
        $this->mail->CharSet = 'UTF8';
    }

    /**
     * 设置发送者的信息
     * @param string $username
     * @param string $password
     * @return $this
     */
    public function setUser(string $username, string $password): static {
        $this->mail->MailServerUserName = $username;
        $this->mail->MailServerPassword = $password;
        return $this;
    }

    /**
     * 设置发件人
     * @param string $address
     * @param string $name
     * @param bool|string $auto
     * @return $this
     */
    public function setFrom(string $address, string $name = '', bool $auto = true): static {
        $this->mail->From = $address;
        $this->mail->FromName = $name;
        return $this;
    }

    /**
     * 添加接收者 可以添加多个但必须保证 接收方服务器一致
     * @param string $address
     * @param string $name
     * @return $this
     */
    public function addAddress(string $address, string $name = ''): static {
        $this->mail->AddRecipient($address, $name);
        return $this;
    }

    /**
     * 发送的内容是否是html 或 plain
     * @param bool $isHtml
     * @return $this
     */
    public function isHtml(bool $isHtml = true): static {
        if ($isHtml) {
            $this->mail->ContentType = 'Text/html';
        }
        return $this;
    }

    /**
     * 发送
     * @param string $subject
     * @param string $body
     * @param string $altBody
     * @return bool
     */
    public function send(string $subject, string $body, string $altBody = ''): bool {
        $this->mail->Subject = $subject;
        $this->mail->Body = $body;
        $this->mail->Send('');
        return true;
    }

    /**
     * 获取错误信息
     */
    public function getError(): string {
        return '';
    }
}