<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatEvent implements ShouldBroadcast {
	use Dispatchable, InteractsWithSockets, SerializesModels;

	private $room, $data;

	/**
	 * Create a new event instance.
	 * 
	 * @return void
	 */
	public function __construct($room, $data) {
		$this->room = $room;
		$this->data = $data;
	}

	/**
	 * Get the channels the event should broadcast on.
	 * 
	 * @return \Illuminate\Broadcasting\Channel|array
	 */
	public function broadcastOn() {
		return new Channel('chat');
	}

	public function broadcastAs() {
		return $this->room;
	}

	public function broadcastWith() {
		return $this->data;
	}
}
