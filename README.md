# Laravel Blog
Blog based on laravel 6.2 framework.

## 1. User authentication
  * Add composer dependency: composer require laravel/ui
  * Generate UI view: ./artisan ui vue --auth
  * Install node.js dependency：npm install
  * Compile vue：npm run dev
  * Create database table：./artisan migrate:refresh
  * **Forgot Your Password? ** Error in page sending email
      * local.ERROR: Expected response code 250 but got code "553", with message "553 Mail from must equal authorized user
      * **Solve:** Add MAIL_FROM_ADDRESS and MAIL_FROM_NAME to the configuration file ".env", and MAIL_FROM_ADDRESS is equal to MAIL_USERNAME.

## 2. Add RabbitMQ message queuing support
  1. Composer installation laravel-queue-rabbitmq
```shell
composer require vladimir-yuldashev/laravel-queue-rabbitmq
```
  2. In the config/app.php file of providers array, Add:
```php
VladimirYuldashev\LaravelQueueRabbitMQ\LaravelQueueRabbitMQServiceProvider::class,
```
  3. Add the following configuration to the connections array in the app / config / queue.php configuration file
```php
'rabbitmq' => [

            'driver' => 'rabbitmq',

            'dsn' => env('RABBITMQ_DSN', null),

            /*
             * Could be one a class that implements \Interop\Amqp\AmqpConnectionFactory for example:
             *  - \EnqueueAmqpExt\AmqpConnectionFactory if you install enqueue/amqp-ext
             *  - \EnqueueAmqpLib\AmqpConnectionFactory if you install enqueue/amqp-lib
             *  - \EnqueueAmqpBunny\AmqpConnectionFactory if you install enqueue/amqp-bunny
             */

            'factory_class' => Enqueue\AmqpLib\AmqpConnectionFactory::class,

            'host' => env('RABBITMQ_HOST', '127.0.0.1'),
            'port' => env('RABBITMQ_PORT', 5672),

            'vhost' => env('RABBITMQ_VHOST', '/'),
            'login' => env('RABBITMQ_LOGIN', 'guest'),
            'password' => env('RABBITMQ_PASSWORD', 'guest'),

            'queue' => env('RABBITMQ_QUEUE', 'default'),

            'options' => [

                'exchange' => [

                    'name' => env('RABBITMQ_EXCHANGE_NAME'),

                    /*
                     * Determine if exchange should be created if it does not exist.
                     */

                    'declare' => env('RABBITMQ_EXCHANGE_DECLARE', true),

                    /*
                     * Read more about possible values at https://www.rabbitmq.com/tutorials/amqp-concepts.html
                     */

                    'type' => env('RABBITMQ_EXCHANGE_TYPE', \Interop\Amqp\AmqpTopic::TYPE_DIRECT),
                    'passive' => env('RABBITMQ_EXCHANGE_PASSIVE', false),
                    'durable' => env('RABBITMQ_EXCHANGE_DURABLE', true),
                    'auto_delete' => env('RABBITMQ_EXCHANGE_AUTODELETE', false),
                    'arguments' => env('RABBITMQ_EXCHANGE_ARGUMENTS'),
                ],

                'queue' => [

                    /*
                     * Determine if queue should be created if it does not exist.
                     */

                    'declare' => env('RABBITMQ_QUEUE_DECLARE', true),

                    /*
                     * Determine if queue should be binded to the exchange created.
                     */

                    'bind' => env('RABBITMQ_QUEUE_DECLARE_BIND', true),

                    /*
                     * Read more about possible values at https://www.rabbitmq.com/tutorials/amqp-concepts.html
                     */

                    'passive' => env('RABBITMQ_QUEUE_PASSIVE', false),
                    'durable' => env('RABBITMQ_QUEUE_DURABLE', true),
                    'exclusive' => env('RABBITMQ_QUEUE_EXCLUSIVE', false),
                    'auto_delete' => env('RABBITMQ_QUEUE_AUTODELETE', false),
                    'arguments' => env('RABBITMQ_QUEUE_ARGUMENTS'),
                ],
            ],

            /*
             * Determine the number of seconds to sleep if there's an error communicating with rabbitmq
             * If set to false, it'll throw an exception rather than doing the sleep for X seconds.
             */

            'sleep_on_error' => env('RABBITMQ_ERROR_SLEEP', 5),

            /*
             * Optional SSL params if an SSL connection is used
             * Using an SSL connection will also require to configure your RabbitMQ to enable SSL. More details can be founds here: https://www.rabbitmq.com/ssl.html
             */

            'ssl_params' => [
                'ssl_on' => env('RABBITMQ_SSL', false),
                'cafile' => env('RABBITMQ_SSL_CAFILE', null),
                'local_cert' => env('RABBITMQ_SSL_LOCALCERT', null),
                'local_key' => env('RABBITMQ_SSL_LOCALKEY', null),
                'verify_peer' => env('RABBITMQ_SSL_VERIFY_PEER', true),
                'passphrase' => env('RABBITMQ_SSL_PASSPHRASE', null),
            ],

        ],
```
  * 4. Modify  .env configuration file
```php
QUEUE_CONNECTION=rabbitmq

# Here are the new configurations
RABBITMQ_HOST=127.0.0.1
RABBITMQ_PORT=5672
RABBITMQ_VHOST=/
RABBITMQ_LOGIN=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_QUEUE=queue_name
```
  5. Create task class
```shell
./artisan make:job Queue
```
After execution, a file app/Jobs/Queue.php will be generated. For example:
```php
<?php

namespace App\Jobs;

use App\Entities\Posts;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class Queue  implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $data;

    /**
     * Queue constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        try{
            $insert = [
                'title'=>$this->data->title,
                'author_id'=>$this->data->author_id,
                'content'=>$this->data->content,
                'description'=>$this->data->description,
            ];
            $result = Posts::create($insert);
            echo json_encode(['code' => 200, 'msg' => $result]);
        }catch (\Exception $exception) {
            echo json_encode(['code'=>0,'msg'=>$exception->getMessage()]);
        }

    }
}
```
  6. Production, put data into MQ queue
```php
<?php

namespace App\Http\Controllers;

use App\Entities\CostaNews;
use App\Jobs\Queue;

class IndexController extends Controller
{

    public function index()
    {
        $data = CostaNews::get();
        foreach ($data as $item) {
            $this->dispatch(new Queue($item));
        }
        return response()->json(['code'=>0, 'msg'=>"success"]);
    }

}
```
  7. Consumption queue
Execute command to consume:
```shell
./artisan queue:work rabbitmq
```
The effect is as follows:
```
root@9e99cf9fba73:/var/www/blog# php artisan  queue:work rabbitmq
[2018-12-24 07:34:32][5c208bf66e63b3.56379160] Processing: App\Jobs\Queue
{"code":200,"msg":{"title":1,"author_id":2,"content":"\u5185\u5bb9","description":"\u63cf\u8ff0","updated_at":"2018-12-24 07:34:32","created_at":"2018-12-24 07:34:32","id":1}}[2018-12-24 07:34:32][5c208bf66e63b3.56379160] Processed:  App\Jobs\Queue
[2018-12-24 07:34:32][5c208bf66ff7c3.20969590] Processing: App\Jobs\Queue
{"code":200,"msg":{"title":2,"author_id":2,"content":"\u5185\u5bb92","description":"\u63cf\u8ff02","updated_at":"2018-12-24 07:34:32","created_at":"2018-12-24 07:34:32","id":2}}[2018-12-24 07:34:32][5c208bf66ff7c3.20969590] Processed:  App\Jobs\Queue
[2018-12-24 07:34:32][5c208bf6702695.93123122] Processing: App\Jobs\Queue
{"code":200,"msg":{"title":3,"author_id":2,"content":"\u5185\u5bb93","description":"\u63cf\u8ff03","updated_at":"2018-12-24 07:34:32","created_at":"2018-12-24 07:34:32","id":3}}[2018-12-24 07:34:32][5c208bf6702695.93123122] Processed:  App\Jobs\Queue
[2018-12-24 07:34:32][5c208bf6706e24.78015170] Processing: App\Jobs\Queue
{"code":200,"msg":{"title":4,"author_id":2,"content":"\u5185\u5bb94","description":"\u63cf\u8ff04","updated_at":"2018-12-24 07:34:32","created_at":"2018-12-24 07:34:32","id":4}}[2018-12-24 07:34:32][5c208bf6706e24.78015170] Processed:  App\Jobs\Queue
[2018-12-24 07:34:32][5c208bf6709be0.07998731] Processing: App\Jobs\Queue
{"code":200,"msg":{"title":5,"author_id":2,"content":"\u5185\u5bb95","description":"\u63cf\u8ff05","updated_at":"2018-12-24 07:34:32","created_at":"2018-12-24 07:34:32","id":5}}[2018-12-24 07:34:32][5c208bf6709be0.07998731] Processed:  App\Jobs\Queue
```
**Note:** to use the laravel queue rabbitmq package, you need to enable the sockets expansion, otherwise an error will be reported.

## 3. Chat room based on websocket
  1. Configure the default broadcast driver as redis. Set the BROADCAST_DRIVER parameter in the . env configuration file as redis. You must start the redis service.
  2. Add configuration parameters to configuration .env
```ini
WEBSOCKET_URL=http://localhost:6001
WEBSOCKET_QUEUE=laravel_chat
```
  3. Update configuration cache: ./artisan config:clear
  4. Start the queue daemons in laravel: ./artisan queue:work --sleep=0
  5. Generate event message class: ./artisan make:event ChatEvent
  6. Add get and post routes for chat:
```php
Route::middleware('auth:web')->group(function() {
	Route::get('/chat', function() {
		header('Origin: ' . urlencode(env('WEBSOCKET_URL')));
		return view('chat');
	})->name('chat');
	Route::post('/chat', function() {
		$room = request()->post('room');
		$name = request()->post('name');
		$message = request()->post('message');
		
		if(!preg_match('/^\\w+$/', $room)) return 'Room format error';
		if(!preg_match('/^\\w+$/', $name)) return 'Name format error';
		if(!$message) return 'Message cannot be empty';
		
		broadcast(new ChatEvent($room, compact('name', 'message')));
		
		return 'Pushed to queue';
	});
});
```
  7. Add the view file chat.blade.php:
```html
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Websocket of chat room</div>
                <form class="card-body" id="j-chat-form">
                	<div class="row">
                		<button id="j-connect" class="btn btn-primary mx-3" type="button">Connect</button>
                		<button id="j-disconnect" class="btn btn-primary mx-3" type="button" disabled="disabled">Disconnect</button>
                		<button id="j-time" class="btn btn-primary" type="button" disabled="disabled">Get server time</button>
                		<span class="ml-3 col-form-label">Name: <span id="j-name" class="font-weight-bold font-italic"></span></span>
                	</div>
	                <div class="row mt-3 mb-3">
	                	<div class="col-2"><input id="j-room" class="form-control" type="text" value="default" /></div>
	                	<div class="col-8"><input id="j-message" class="form-control" type="text" value="" /></div>
	                	<div class="col-2"><input id="j-submit" class="form-control btn btn-primary" type="submit" value="Send" disabled="disabled" /></div>
	                </div>
	                <h5 class="mb-0">Message:</h5>
	                <div id="j-message-box" class="message-box"></div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@section('end-page')
<style type="text/css">
.message-box p{margin:5px 0;}
</style>
<script type="text/javascript" src="{{ env('WEBSOCKET_URL') }}/socket.io/socket.io.js"></script>
<script type="text/javascript">
document.body.onload = function() {
	var sock = false;
	var room = false;
	var name = 'N' + parseInt(Math.random() * 100000);

	$('#j-name').text(name);

	function time() {
		var d = new Date();
		var t = [d.getHours(),d.getMinutes(),d.getSeconds()];
		var i;
		for(i=0; i<t.length; i++) {
			if(t[i] < 10) t[i] = '0' + t[i];
		}
		return t.join(':');
	}

	$('#j-connect').click(function() {
		room = $.trim($('#j-room').val());
		if(!/\w+/.test(room)) {
			$('#j-message-box').prepend('<p>' + time() + ' <span class="text-danger">Room name cannot be empty</span></p>');
			return false;
		}
		sock = io('{{ env('WEBSOCKET_URL') }}');
		sock.on('connect', function() {
			// sock.send('Hello World!');
			sock.emit('join', {room:room,name:name});
			$('#j-message-box').prepend('<p>' + time() + ' <span class="text-success">Connected</span></p>');
			$('#j-connect').attr('disabled', true);
			$('#j-disconnect,#j-time,#j-submit').attr('disabled',false);
			sock = this;
		});
		sock.on('disconnect', function() {
			$('#j-message-box').prepend('<p>' + time() + ' <span class="text-info">Disconnected</span></p>');
			$('#j-connect').attr('disabled', false);
			$('#j-disconnect,#j-time,#j-submit').attr('disabled', true);
			sock.close();
			sock = false;
		});
		sock.on('error', function(err) {
			$('#j-message-box').prepend('<p>' + time() + ' <span class="text-danger">Error</span> ' + err + '</p>');
		});
		sock.on('message', function(msg) {
			$('#j-message-box').prepend('<p>' + time() + ' ' + msg + '</p>');
		});
		sock.on('join', function(msg) {
			$('#j-message-box').prepend('<p>' + time() + ' <span class="font-weight-bold">' + msg + '</span> is joined</p>');
		});
		sock.on('leave', function(msg) {
			$('#j-message-box').prepend('<p>' + time() + ' <span class="font-weight-bold">' + msg + '</span> is leaved</p>');
		});
		sock.on('time', function(msg) {
			$('#j-message-box').prepend('<p>' + time() + ' <span class="font-weight-bold">' + msg.name + '</span> The server time is ' + msg.time + '</p>');
		});
		sock.on('chat', function(msg) {
			if(msg === 'HELO') return;
			var val = $.trim($('#j-message').val());
			if(val == msg.message) $('#j-message').val('');
			$('#j-message-box').prepend('<p>' + time() + ' <span class="font-weight-bold">' + msg.name + '</span> ' + msg.message + '</p>');
		});
	});

	$('#j-disconnect').click(function() {
		sock.close();
	});

	$('#j-time').click(function() {
		$('#j-message-box').prepend('<p>' + time() + ' <span class="text-info">Getting server time...</span></p>');
		sock.emit('time');
	});
	
	$('#j-chat-form').submit(function() {
		var msg = $.trim($('#j-message').val());
		if(msg.length == 0) {
			$('#j-message-box').prepend('<p>' + time() + ' <span class="text-danger">Message cannot be empty</span></p>');
			return false;
		}
		$p = $('<p>' + time() + ' Sending "' + msg + '" to ' + room + ' room ... </p>').prependTo('#j-message-box');
		$.ajax({
			type: 'POST',
			url: location.href,
			data: {room:room, name:name, message:msg, _token: '{{ csrf_token() }}'},
			success:function(data) {
				$p.append('<span class="text-info">' + data + '</span> ');
			},
			error: function(err) {
				$p.append('<span class="text-danger">error</span> ' + err);
			}
		});
		return false;
	});
};
</script>
@endsection
```
  8. Add websocket server.js of node.js:
```js
// npm install socket.io ioredis moment dotenv
var app = require('http').createServer(function (req, res) {
	res.writeHead(200);
	res.end('');
});
var io = require('socket.io')(app);

let dotenv = require('dotenv');
dotenv.config('./env');

var Redis = require('ioredis');
var redis = new Redis(process.env.REDIS_PORT, process.env.REDIS_HOST);

var moment = require("moment");
var rooms = {};

// console.log(process.env.WEBSOCKET_QUEUE);

app.listen(6001, function() {
	console.log('Server is running!');
});

io.on('connection', function(socket) {
	// socket.compress(false).send('Hello world!');
	socket.on('disconnect', function() {
		socket.leave(socket.room);
		io.to(socket.room).emit('leave', socket.name);
	});
	socket.on('join', function(join) {
		console.log(join.name, 'join', join.room);
		socket.join(join.room);
		
		socket.name = join.name;
		socket.room = join.room;
		io.to(socket.room).emit('join', socket.name);
	});
	socket.on('message', function(message) {
		console.log('Receive message:', message);
		socket.send(message);
	});
	socket.on('time', function() {
		var t = moment().format('YYYY-MM-DD HH:mm:ss');
		console.log('time:', socket.room, socket.name, t);
		io.to(socket.room).emit('time', {name:socket.name, time:t});
	});
	socket.on('disconnect', function() {
		console.log('user disconnect')
	});
});

redis.psubscribe(process.env.WEBSOCKET_QUEUE + '*', function(err, count) {
});
redis.on('pmessage', function(subscrbed, channel, message) {
	message = JSON.parse(message);
	console.log(channel + ':' + message.event, message.data);
	io.to(message.event).emit('chat', message.data);
});
```
  9. Start websocket server: node server.js
  10. After the user logs in to the system, there is a chat connection in home, which can be used normally.
