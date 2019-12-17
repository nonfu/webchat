<?php
namespace App\Services\WebSocket;

use App\Services\Websocket\Rooms\RoomContract;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Swoole\WebSocket\Server;

class WebSocket
{
    use Authenticatable;

    const PUSH_ACTION = 'push';
    const EVENT_CONNECT = 'connect';
    const USER_PREFIX = 'uid_';

    /**
     * Websocket Server
     * @var Server
     */
    protected $server;

    /**
     * Determine if to broadcast.
     *
     * @var boolean
     */
    protected $isBroadcast = false;

    /**
     * Scoket sender's fd.
     *
     * @var integer
     */
    protected $sender;

    /**
     * Recepient's fd or room name.
     *
     * @var array
     */
    protected $to = [];

    /**
     * Websocket event callbacks.
     *
     * @var array
     */
    protected $callbacks = [];

    /**
     * Room adapter.
     *
     * @var RoomContract
     */
    protected $room;

    /**
     * DI Container.
     *
     * @var \Illuminate\Contracts\Container\Container
     */
    protected $container;

    /**
     * Websocket constructor.
     *
     * @param RoomContract $room
     */
    public function __construct(RoomContract $room)
    {
        $this->room = $room;
    }

    /**
     * Set broadcast to true.
     */
    public function broadcast(): self
    {
        $this->isBroadcast = true;

        return $this;
    }

    /**
     * Set multiple recipients fd or room names.
     *
     * @param integer, string, array
     *
     * @return $this
     */
    public function to($values): self
    {
        $values = is_string($values) || is_integer($values) ? func_get_args() : $values;

        foreach ($values as $value) {
            if (! in_array($value, $this->to)) {
                $this->to[] = $value;
            }
        }
Log::info('to:' . json_encode($this->to));
        return $this;
    }

    /**
     * Join sender to multiple rooms.
     *
     * @param string, array $rooms
     *
     * @return $this
     */
    public function join($rooms): self
    {
        $rooms = is_string($rooms) || is_integer($rooms) ? func_get_args() : $rooms;

        $this->room->add($this->sender, $rooms);

        return $this;
    }

    /**
     * Make sender leave multiple rooms.
     *
     * @param array $rooms
     *
     * @return $this
     */
    public function leave($rooms = []): self
    {
        $rooms = is_string($rooms) || is_integer($rooms) ? func_get_args() : $rooms;

        $this->room->delete($this->sender, $rooms);

        return $this;
    }

    /**
     * Emit data and reset some status.
     *
     * @param string
     * @param mixed
     *
     * @return boolean
     */
    public function emit(string $event, $data): bool
    {
        $fds = $this->getFds();
        $assigned = !empty($this->to);

        // if no fds are found, but rooms are assigned
        // that means trying to emit to a non-existing room
        // skip it directly instead of pushing to a task queue
        if (empty($fds) && $assigned) {
            return false;
        }

        $payload = [
            'sender'    => $this->sender,
            'fds'       => $fds,
            'broadcast' => $this->isBroadcast,
            'assigned'  => $assigned,
            'event'     => $event,
            'message'   => $data,
        ];
Log::info('payload:' . json_encode($payload));
        $server = app('swoole');
        $pusher = Pusher::make($payload, $server);
        $parser = app('swoole.parser');
        $pusher->push($parser->encode($pusher->getEvent(), $pusher->getMessage()));

        $this->reset();

        return true;
    }

    /**
     * An alias of `join` function.
     *
     * @param string
     *
     * @return $this
     */
    public function in($room)
    {
        $this->join($room);

        return $this;
    }

    /**
     * Register an event name with a closure binding.
     *
     * @param string
     * @param callback
     *
     * @return $this
     */
    public function on(string $event, $callback)
    {
        if (! is_string($callback) && ! is_callable($callback)) {
            throw new \InvalidArgumentException(
                'Invalid websocket callback. Must be a string or callable.'
            );
        }

        $this->callbacks[$event] = $callback;

        return $this;
    }

    /**
     * Check if this event name exists.
     *
     * @param string
     *
     * @return boolean
     */
    public function eventExists(string $event)
    {
        return array_key_exists($event, $this->callbacks);
    }

    /**
     * Execute callback function by its event name.
     *
     * @param string
     * @param mixed
     *
     * @return mixed
     */
    public function call(string $event, $data = null)
    {
        if (! $this->eventExists($event)) {
            return null;
        }

        // inject request param on connect event
        $isConnect = $event === static::EVENT_CONNECT;
        $dataKey = $isConnect ? 'request' : 'data';

        return App::call($this->callbacks[$event], [
            'websocket' => $this,
            $dataKey => $data,
        ]);
    }

    /**
     * Set sender fd.
     *
     * @param integer
     *
     * @return $this
     */
    public function setSender(int $fd)
    {
        $this->sender = $fd;

        return $this;
    }

    /**
     * Get current sender fd.
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Get broadcast status value.
     */
    public function getIsBroadcast()
    {
        return $this->isBroadcast;
    }

    /**
     * Get push destinations (fd or room name).
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Get all fds we're going to push data to.
     */
    protected function getFds()
    {
        $fds = array_filter($this->to, function ($value) {
            return is_integer($value);
        });
        $rooms = array_diff($this->to, $fds);

        foreach ($rooms as $room) {
            $clients = $this->room->getClients($room);
            // fallback fd with wrong type back to fds array
            if (empty($clients) && is_numeric($room)) {
                $fds[] = $room;
            } else {
                $fds = array_merge($fds, $clients);
            }
        }

        return array_values(array_unique($fds));
    }

    /**
     * Reset some data status.
     *
     * @param bool $force
     *
     * @return $this
     */
    public function reset($force = false)
    {
        $this->isBroadcast = false;
        $this->to = [];

        if ($force) {
            $this->sender = null;
            $this->userId = null;
        }

        return $this;
    }
}
