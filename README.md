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


