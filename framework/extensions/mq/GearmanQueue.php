<?php
namespace rock\mq;

use rock\Rock;

class GearmanQueue extends Queue implements QueueInterface
{
    const PRIORITY_NORMAL = 1;
    const PRIORITY_LOW = 2;
    const PRIORITY_HIGH = 3;

    public $dns= '127.0.0.1:4730';

    public $priority = self::PRIORITY_NORMAL;

    /**
     * @inheritdoc
     * @return \GearmanClient
     */
    public function sendBackground($message)
    {
        if (!$this->beforeSend()) {
            return null;
        }
        $client = $this->client();
        switch ($this->priority) {
            case self::PRIORITY_NORMAL:
                $client->doBackground($this->id, $message);
                break;
            case self::PRIORITY_LOW:
                $client->doLowBackground($this->id, $message);
                break;
            default:
                $client->doHighBackground($this->id, $message);
                break;
        }
        // Check the return code - what other codes are there and what do they mean?
        if($client->returnCode() != GEARMAN_SUCCESS){
            throw new MQException('Bad return code: ' . $client->returnCode());
        }

        $this->afterSend();
        return $client;
    }

    /**
     * @inheritdoc
     */
    public function send($message = '', $limit = -1)
    {
        if (!$this->beforeSend()) {
            return null;
        }
        $client = $this->client();
        if ($this->blocking) {
            $result = null;
            switch ($this->priority) {
                case self::PRIORITY_NORMAL:
                    $result = $client->doNormal($this->id, $message);
                    break;
                case self::PRIORITY_LOW:
                    $result = $client->doLow($this->id, $message);
                    break;
                default:
                    $result = $client->doHigh($this->id, $message);
                    break;
            }
            $this->afterSend($result);
            return $result;
        } else {
            $count = 0;
            $client->setTimeout($this->timeout * 1000);
            while ($limit == -1 || $count < $limit) {
                switch ($this->priority) {
                    case self::PRIORITY_NORMAL:
                        $result = @$client->doNormal($this->id, $message);
                        break;
                    case self::PRIORITY_LOW:
                        $result = @$client->doLow($this->id, $message);
                        break;
                    default:
                        $result = @$client->doHigh($this->id, $message);
                        break;
                }
                if ($result) {
                    $this->afterSend($result);
                    return $result;
                }
                ++$count;
            }
        }

        Rock::error('The receive timed out.');
        return null;
    }

    /**
     * @inheritdoc
     */
    public function receive(callable $callback, $limit = -1)
    {
        $worker = new \GearmanWorker();
        $worker->addServers($this->dns);
        $worker->addFunction($this->id, $callback);

        /* Loop receiving and echoing back */
        $count = 0;
        while ($limit == -1 || $count < $limit) {
            if (!$worker->work()) {
                break;
            }
            $count++;
        }
    }

    /**
     * @inheritdoc
     * @throws MQException
     */
    public function publish(array $topics, $limit = -1)
    {
        throw new MQException(MQException::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @inheritdoc
     * @throws MQException
     */
    public function subscribe($topic = '', $limit = -1, $message = '')
    {
        throw new MQException(MQException::UNKNOWN_METHOD, ['method' => __METHOD__]);
    }

    /**
     * @return \GearmanClient
     * @throws MQException
     */
    protected function client()
    {
        $client = new \GearmanClient();
        $client->addServers($this->dns);
        if (($haveGoodServer = $client->ping($this->id)) === false) {
            throw new MQException("Server does not access: {$this->dns}");
        }
        return $client;
    }
}


