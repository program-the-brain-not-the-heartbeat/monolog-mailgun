<?php

namespace Tylercd100\Monolog\Handler;

use Exception;
use Monolog\Logger;
use Monolog\Handler\MailHandler;

/**
 * MailgunHandler uses cURL to send the emails to the Mailgun API
 *
 * @author Tyler Arbon <tylercd100@gmail.com>
 */
class MailgunHandler extends MailHandler
{
    protected $message;
    protected $apiKey;

    public function __construct($to, $subject, $from, $token, $domain, $extra = [], $type = 'text', $level = Logger::CRITICAL, $bubble = true, $host = 'api.mailgun.net', $version = 'v3')
    {
        if ($version !== 'v3') {
            throw new Exception("Version '{$version}' is not supported");
        }
        
        if (!in_array($type, [ 'text', 'html' ])) {
            throw new Exception("Type '{$type}' is not supported");
        }
        $this->to = $to;
        $this->subject = $subject;
        $this->from = $from;
        $this->host = $host;
        $this->version = $version;
        $this->domain = $domain;
        $this->token = $token;
        $this->type = $type;
        $this->extra = $extra;

        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    protected function send(string $content, array $records): void
    {
        $auth = base64_encode("api:".$this->token);

        $fields = http_build_query(array_merge($this->extra, [
            'from'    => $this->from,
            'to'      => $this->to,
            'subject' => $this->subject,
            $this->type => $content
        ]));

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://{$this->host}/{$this->version}/{$this->domain}/messages");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Basic ".$auth
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

        curl_exec($ch);
        curl_close($ch);
    }
}
