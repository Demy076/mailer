<?php

namespace Demy;

require_once dirname(__FILE__) . '/../vendor/autoload.php';

use Monolog\Level,
    Monolog\Logger,
    Monolog\Handler\StreamHandler;

class mailer extends \PHPMailer\PHPMailer\PHPMailer
{
    public $_config = [];
    private array|object $monolog;
    public function __construct(array $config = [])
    {
        $this->_config = $config;
        $this->monolog =  (new Logger('mailer'))->pushHandler(new StreamHandler(dirname(__FILE__) . '/../logs/mailer.log', Level::Warning));
    }
    private function sortItems(array $items = []): string
    {
        if (empty($items)) {
            return "";
        }
        if (is_array($items) && count($items) > 1) {
            return preg_replace('/,(?=[^,]*$)/', ' and', implode(", ", $items));
        } else {
            $items = implode("", $items);
        }
        return $items;
    }
    private function validateConfig()
    {
        if (empty($this->_config)) {
            return [
                'status' => "error",
                'message' => 'Config is empty'
            ];
        }
        $allowedKeys = [
            "host",
            "port",
            "username",
            "password",
            "isHTML",
            "from",
            "to",
            "subject",
            "body"

        ];
        $missingKeys = array_diff($allowedKeys, array_keys($this->_config));
        if (!empty($missingKeys)) {
            return [
                'status' => "error",
                'message' => 'Missing keys: ' . $this->sortItems($missingKeys)
            ];
        }
        $keyCheck = array_diff_key($this->_config, array_flip($allowedKeys));
        if (!empty($keyCheck)) {
            return [
                'status' => "error",
                'message' => 'Invalid config keys: ' . $this->sortItems($keyCheck)
            ];
        }
        return [
            "status" => "success",
            "message" => "Config is valid"
        ];
    }
    private function initiateMailer()
    {
        if (is_string($this->_config["to"] !== true && \is_array($this->_config["to"]) !== true)) {
            return [
                "status" => "error",
                "message" => "Email address(es) are not valid"
            ];
        }
        if (is_array($this->_config["to"])) {
            $invalidEmails = [];
            array_map(function ($email) use (&$invalidEmails) {
                if (filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false) {
                    $this->monolog->warning("Email {$email} is not valid");
                    $invalidEmails[] = $email;
                }
            }, array_unique(array_filter($this->_config["to"], function ($email) {
                return !empty($email);
            })) ?? []);
            if (!empty($invalidEmails) && \is_array($invalidEmails)) {
                return [
                    "status" => "error",
                    "message" => "Email addresses are not valid: " . $this->sortItems($invalidEmails)
                ];
            }
            // Add all addresses to the mailer
            array_map(function ($email) {
                $this->addAddress($email);
            }, $this->_config["to"]);
        } else {
            $this->addAddress($this->_config['to']);
        }
        $this->isSMTP();
        $this->Host = $this->_config['host'];
        $this->Port = $this->_config['port'];
        $this->SMTPAuth = true;
        $this->SMTPSecure = 'tls';
        $this->Port = $this->_config['port'];
        $this->Username = $this->_config['username'];
        $this->Password = $this->_config['password'];
        $this->isHTML($this->_config['isHTML']);
        $this->setFrom($this->_config['from']);
        $this->Subject = $this->_config["subject"];
        $this->Body = $this->_config["body"];
        $this->send();
        return $this;
    }
    public function sendMail()
    {
        $configCheck = $this->validateConfig();
        if ($configCheck['status'] == "error") {
            return $configCheck;
        }
        $mailerInst = $this->initiateMailer();
        if (is_object($mailerInst) !== true && isset($mailerInst['status']) && $mailerInst['status'] == "error") {
            return $mailerInst;
        }
        if ($mailerInst) {
            $this->monolog->alert("Email sent successfully => " . $this->sortItems(array_keys($mailerInst->getAllRecipientAddresses())) . " Subject: " . $mailerInst->Subject . " message: " . $mailerInst->Body . "");
            return [
                "status" => "success",
                "message" => "Email sent successfully to " . $this->sortItems(array_keys($mailerInst->getAllRecipientAddresses())) . ""
            ];
        } else {
            $this->monolog->error($this->ErrorInfo);
            return [
                "status" => "error",
                "message" => "Error: " . $this->ErrorInfo
            ];
        }
    }
}
