<?php
namespace App\Events;

use App\Message;
use Carbon\Carbon;
use Hhxsv5\LaravelS\Swoole\Task\Event;

class MessageReceived extends Event
{
    private $message;
    private $userId;

    /**
     * Create a new event instance.
     */
    public function __construct($message, $userId = 0)
    {
        $this->message = $message;
        $this->userId = $userId;
    }

    /**
     * Get the message data
     * 
     * return App\Message
     */
    public function getData()
    {
        $model = new Message();
        $model->room_id = $this->message->room_id;
        $model->msg = $this->message->type == 'text' ? $this->message->content : '';
        $model->img = $this->message->type == 'image' ? $this->message->image : '';
        $model->user_id = $this->userId;
        $model->created_at = Carbon::now();
        return $model;
    }
}
