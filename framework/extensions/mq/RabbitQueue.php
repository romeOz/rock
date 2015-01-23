<?php
namespace rock\mq;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;
use rock\base\BaseException;
use rock\log\Log;

class RabbitQueue extends Queue implements QueueInterface
{
    public $dns = 'localhost:5672';
    public $exchange = 'exchange';
    public $user = 'guest';
    public $password = 'guest';
    public $properties = [];
    public $type = 'fanout';

    protected $host;
    protected $port;

    public function init()
    {
        list($this->host, $this->port) = explode(':', $this->dns);
        $this->port = (int)$this->port;
    }

    /**
     * @inheritdoc
     * @return \PhpAmqpLib\Channel\AMQPChannel|null
     */
    public function sendBackground($message)
    {
        if (!$this->beforeSend()) {
            return null;
        }
        /**
         * @var AMQPConnection $connection
         * @var AMQPChannel $channel
         */
        list($connection, $channel) = $this->connection();
        $channel->queue_declare($this->id, false, true, false, false);
        register_shutdown_function(
            function(AMQPChannel $channel, AMQPConnection $connection){
                $channel->close();
                $connection->close();
            },
            $channel,
            $connection
        );

        $msg = new AMQPMessage((string)$message, $this->properties);
        $channel->basic_publish($msg, '', $this->id);
        $this->afterSend();
        return $channel;
    }

    /**
     * @inheritdoc
     */
    public function send($message = '', $limit = -1, callable $callback = null)
    {
        if (!$this->beforeSend()) {
            return null;
        }
        /**
         * @var AMQPConnection $connection
         * @var AMQPChannel $channel
         */
        list($connection, $channel) = $this->connection();
        list($queueName) = $channel->queue_declare('', false, false, true, false);
        $result = null;
        if (!isset($callback)) {
            $id = uniqid();
            $this->properties['correlation_id'] = $id;
            $callback = function(AMQPMessage $msg) use ($id, &$result){
                if ($msg->get('correlation_id') == $id) {
                    $result = $msg->body;
                }
            };
        }
        $channel->basic_consume($queueName, '', false, false, false, false, $callback);
        $this->properties['reply_to'] = $queueName;

        register_shutdown_function(
            function(AMQPChannel $channel, AMQPConnection $connection){
                $channel->close();
                $connection->close();
            },
            $channel,
            $connection
        );

        $msg = new AMQPMessage($message, $this->properties);
        $channel->basic_publish($msg, '', $this->id);

        if ($this->blocking) {
            $channel->wait();
            $this->afterSend($result);
            return $result;
        }

        if (count($channel->callbacks)) {

            // non-blocking
            $count = 0;
            while (count($channel->callbacks) && ($limit == -1 || $count < $limit)) {
                // add here other sockets that you need to attend
                $read = [];
                if(is_resource($connection->getSocket())) {
                    $read = [$connection->getSocket()];
                }
                $write = null;
                $except = null;
                if (false === ($numChangedStreams = stream_select($read, $write, $except, $this->timeout))) {
                    break;
                } elseif ($numChangedStreams > 0) {
                    $channel->wait();
                    $this->afterSend($result);
                    return $result;
                }
                ++$count;
            }
            if (class_exists('\rock\log\Log')) {
                $message = BaseException::convertExceptionToString(new MQException('The receive timed out.'));
                Log::err($message);
            }
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function receive(callable $callback, $limit = -1)
    {
        $connection = new AMQPConnection($this->host, $this->port, $this->user, $this->password);
        $channel = $connection->channel();
        $channel->queue_declare($this->id, false, true, false, false);
        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($this->id, '', false, false, false, false, $callback);

        register_shutdown_function(
            function(AMQPChannel $channel, AMQPConnection $connection){
                $channel->close();
                $connection->close();
            },
            $channel,
            $connection
        );

        if (count($channel->callbacks)) {
            $count = 0;
            while(count($channel->callbacks) && ($limit == -1 || $count < $limit)) {
                $channel->wait();
                ++$count;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function publish(array $topics, $limit = -1)
    {
        $connection = new AMQPConnection($this->host, $this->port, $this->user, $this->password);
        $channel = $connection->channel();
        $channel->exchange_declare($this->exchange, $this->type, false, false, false);
        list($queueName) = $channel->queue_declare("", false, false, true, false);
        foreach($topics as $topic => $callback) {
            $channel->queue_bind($queueName, $this->exchange, $topic);
            $channel->basic_consume($queueName, '', false, false, false, false, $callback);
        }
        $channel->basic_qos(null, 1, null);
        $count = 0;
        if (count($channel->callbacks)) {
            while($limit == -1 || $count < $limit) {
                $channel->wait();
                ++$count;
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function subscribe($topic = '', $limit = 1, $message = '')
    {
        if (!$this->beforeSubscription($topic, $message)) {
            return null;
        }
        $connection = new AMQPConnection($this->host, $this->port, $this->user, $this->password);
        $channel = $connection->channel();

        $channel->exchange_declare($this->exchange, $this->type, false, false, false);
        list($queueName) = $channel->queue_declare("", false, false, true, false);

        $correlationId = uniqid();
        $result = null;
        $channel->basic_consume(
            $queueName, '', false, false, false, false,
            function(AMQPMessage $msg) use (&$result, $correlationId){
                if($msg->get('correlation_id') == $correlationId) {
                    $result = $msg->body;
                }
            }
        );

        register_shutdown_function(
            function(AMQPChannel $channel, AMQPConnection $connection){
                $channel->close();
                $connection->close();
            },
            $channel,
            $connection
        );

        $msg = new AMQPMessage(
            $message,
            ['correlation_id' => $correlationId, 'reply_to' => $queueName]
        );
        $channel->basic_publish($msg, $this->exchange, $topic);
        if ($this->blocking) {
            if (count($channel->callbacks)) {
                $channel->wait();
            }
            $this->afterSubscription($topic, $result);
            return $result;
        }

        // non-blocking
        if (count($channel->callbacks)) {
            $count = 0;
            while ($limit == -1 || $count < $limit) {
                // add here other sockets that you need to attend
                $read = [];
                if(is_resource($connection->getSocket())) {
                    $read = [$connection->getSocket()];
                }
                $write = null;
                $except = null;
                if (false === ($num_changed_streams = stream_select($read, $write, $except, $this->timeout))) {
                    break;
                } elseif ($num_changed_streams > 0) {
                    $channel->wait();
                    $this->afterSubscription($topic, $result);
                    return $result;
                }
                ++$count;
            }
            if (class_exists('\rock\log\Log')) {
                $message = BaseException::convertExceptionToString(new MQException('The receive timed out.'));
                Log::err($message);
            }
        }

        return $result;
    }

    /**
     * @return array
     */
    protected function connection()
    {
        $connection = new AMQPConnection($this->host, $this->port, $this->user, $this->password);
        $channel = $connection->channel();
        return [$connection, $channel];
    }
}