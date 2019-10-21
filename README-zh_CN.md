# Laravel 博客
基于Laravel 6.2框架的博客。

## 1. 用户认证
  * 添加composer依赖：composer require laravel/ui
  * 生成UI视图：./artisan ui vue --auth
  * 安装node依赖：npm install
  * 编译vue：npm run dev
  * 创建数据库表：./artisan migrate:refresh
  * **Forgot Your Password? ** 页面发送邮件时报错
      * local.ERROR: Expected response code 250 but got code "553", with message "553 Mail from must equal authorized user
      * **解决：**在配置文件.env中添加MAIL_FROM_ADDRESS和MAIL_FROM_NAME，并且MAIL_FROM_ADDRESS与MAIL_USERNAME相等。

## 2. 添加RabbitMQ消息队列支持
  1. Composer 安装 laravel-queue-rabbitmq
```shell
composer require vladimir-yuldashev/laravel-queue-rabbitmq
```
  2. 在 config/app.php 文件中，providers 中添加：
```php
VladimirYuldashev\LaravelQueueRabbitMQ\LaravelQueueRabbitMQServiceProvider::class,
```
  3. 在 app/config/queue.php 配置文件中的 connections 数组中加入以下配置
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
  * 4. 修改 .env 文件
```php
QUEUE_CONNECTION=rabbitmq    #这个配置env一般会有先找到修改为这个

#以下是新增配置

RABBITMQ_HOST=rabbitmq  #mq的服务器地址，我这里用的是laradock，具体的就具体修改咯
RABBITMQ_PORT=5672  #mq的端口
RABBITMQ_VHOST=/
RABBITMQ_LOGIN=guest    #mq的登录名
RABBITMQ_PASSWORD=guest   #mq的密码
RABBITMQ_QUEUE=queue_name   #mq的队列名称
```
  5. 创建任务类
```shell
./artisan make:job Queue
```
执行之后会生成一个文件 app/Jobs/Queue.php，例子：
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
  6. 生产，把数据放进 mq 队列
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
  7. 消费队列
执行命令进行消费：
```shell
./artisan queue:work rabbitmq
```
效果如下：
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
**注意：**使用这个 laravel-queue-rabbitmq 这个包需要开启 sockets 拓展，不然会报错

