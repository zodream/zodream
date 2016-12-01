<?php
namespace Zodream\Infrastructure\Mailer;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/5/27
 * Time: 13:19
 */
use Zodream\Infrastructure\Error\Error;

class JMailer extends BaseMailer {

    /**
     * @var object
     */
    protected $mail;

    public function __construct() {
        $this->mail = new \COM('JMail.Message');
        if (!$this->mail) {
            throw new \Exception('CANNOT USE JMail DLL!');
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
    public function setUser($username, $password) {
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
    public function setFrom($address, $name = '', $auto = TRUE) {
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
    public function addAddress($address, $name = '') {
        $this->mail->AddRecipient($address, $name);
        return $this;
    }

    /**
     * 发送的内容是否是html 或 plain
     * @param bool $isHtml
     * @return $this
     */
    public function isHtml($isHtml = TRUE) {
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
    public function send($subject, $body, $altBody = '') {
        $this->mail->Subject = $subject;
        $this->mail->Body = $body;
        $this->mail->Send('');
        return $this;
    }

    /**
     * 获取错误信息
     */
    public function getError() {
        
    }
}