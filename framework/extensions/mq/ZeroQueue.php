<?php
namespace rock\mq;


use rock\base\BaseException;
use rock\log\Log;

class ZeroQueue extends Queue implements QueueInterface
{
    public $dns = 'tcp://127.0.0.1:5556';

    /**
     * @inheritdoc
     * @return \ZMQSocket
     */
    public function sendBackground($message)
    {
        if (!$this->beforeSend()) {
            return null;
        }
        $socket = $this->connection(\ZMQ::SOCKET_REQ);
        $socket->send($message);
        $this->afterSend();
        return $socket;
    }

    /**
     * @inheritdoc
     */
    public function send($message = '', $limit = -1)
    {
        if (!$this->beforeSend()) {
            return null;
        }
        $socket = $this->connection(\ZMQ::SOCKET_REQ);
        if ($this->blocking) {
            $socket->send($message);
            $result = $socket->recv();
            $this->afterSend($result);
            return $result;
        }

        $count = 0;
        while ($limit == -1 || $count < $limit) {
            try {
                if ($socket->send($message, \ZMQ::MODE_DONTWAIT)) {
                   break;
                }
            } catch (\ZMQSocketException $e) {
                throw new MQException($e->getMessage(), [], $e);
            }
            ++$count;
            sleep($this->timeout);
        }

        $count = 0;
        while ($limit == -1 || $count < $limit) {
            try {
                if ($result = $socket->recv(\ZMQ::MODE_DONTWAIT)) {
                    $this->afterSend($result);
                    return $result;
                }

            } catch (\ZMQSocketException $e) {
                throw new MQException($e->getMessage(), [], $e);
            }
            ++$count;
            sleep($this->timeout);
        }

        if (class_exists('\rock\log\Log')) {
            $message = BaseException::convertExceptionToString(new MQException('The receive timed out.'));
            Log::err($message);
        }
        return null;
    }

    /**
     * @inheritdoc
     */
    public function receive(callable $callback, $limit = -1)
    {
        $socket = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_REP);
        $socket->bind($this->dns);

        /* Loop receiving and echoing back */
        $count = 0;
        while ($limit == -1 || $count < $limit) {
            if ($callback instanceof \Closure) {
                call_user_func($callback, $socket, $this);
            }
            $count++;
        }
    }

    /**
     * @inheritdoc
     */
    public function publish(array $topics, $limit=-1)
    {
        $publisher = new \ZMQSocket(new \ZMQContext(), \ZMQ::SOCKET_PUB);
        $publisher->bind($this->dns);
        $count = 0;
        while ($limit == -1 || $count < $limit) {
            foreach ($topics as $topic => $callback) {
                $publisher->send($topic, \ZMQ::MODE_SNDMORE);
                call_user_func($callback, $publisher, $this);
            }
            $count++;
        }
    }

    /**
     * @inheritdoc
     * @throws MQException
     */
    public function subscribe($topic = '', $limit = -1, $message = '')
    {
        if (!$this->beforeSubscription($topic, $message)) {
            return null;
        }
        $subscriber = $this->connection(\ZMQ::SOCKET_SUB);
        $subscriber->setSockOpt(\ZMQ::SOCKOPT_SUBSCRIBE, $topic);

        if ($this->blocking) {
            $result = $subscriber->recvMulti();
            $result = isset($result[1]) ? $result[1] : null;
            $this->afterSubscription($topic, $result);
            return $result;
        }

        $count = 0;
        while ($limit == -1 || $count < $limit) {
            try {
                $result = $subscriber->recvMulti(\ZMQ::MODE_DONTWAIT);
                if (!empty($result)) {
                    $result = isset($result[1]) ? $result[1] : null;
                    $this->afterSubscription($topic, $result);
                    return $result;
                }
            } catch (\ZMQSocketException $e) {
                throw new MQException($e->getMessage(),[], $e);
            }
            ++$count;
            sleep($this->timeout);
        }

        if (class_exists('\rock\log\Log')) {
            $message = BaseException::convertExceptionToString(new MQException('The receive timed out.'));
            Log::err($message);
        }
        return null;
    }

    /**
     * @param $type
     * @throws MQException
     * @return \ZMQSocket
     */
    protected function connection($type)
    {
        /* Create a socket */
        $socket = new \ZMQSocket(new \ZMQContext(), $type);
        /* Get list of connected endpoints */
        $endpoints = $socket->getEndpoints();
        /* Check if the socket is connected */
        if (!in_array($this->dns, $endpoints['connect'])) {
            $socket->connect($this->dns);
            //$socket->bind($this->dns);
        } else {
            throw new MQException("Already connected to {$this->dns}");
        }

        return $socket;
    }
}