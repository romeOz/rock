<?php
namespace rock\mail;


use rock\events\EventsInterface;
use rock\events\EventsTrait;

class Mail extends \PHPMailer implements EventsInterface
{
    use EventsTrait {
        EventsTrait::__construct as parentConstruct;
    }
    public $charset = 'utf-8';

    public function __construct($configs = [])
    {
        $this->parentConstruct($configs);
        parent::__construct(true);
    }

    public function init()
    {
        if (is_callable($this->charset)) {
            $this->charset = call_user_func($this->charset, $this);
        }
        $this->CharSet = $this->charset;
        $this->isHTML();
    }

    /**
     * Set address recipient.
     *
     * @param string $address
     * @param string $name
     * @return $this
     */
    public function address($address, $name = "")
    {
        $this->addAddress($address, $name);
        return $this;
    }

    /**
     * Subject
     *
     * @param string $subject
     * @return $this
     */
    public function subject($subject)
    {
        $this->Subject = $subject;
        return $this;
    }

    /**
     * Body
     *
     * @param string $body
     * @return $this
     */
    public function body($body)
    {
        $this->Body = $body;
        return $this;
    }
}