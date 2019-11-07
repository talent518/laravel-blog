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
		console.log(socket.name + ' leave room ' + socket.room);
		io.to(socket.room).emit('leave', socket.name);
		socket.leave(socket.room);
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
});

redis.psubscribe(process.env.WEBSOCKET_QUEUE + '*', function(err, count) {
});
redis.on('pmessage', function(subscrbed, channel, message) {
	message = JSON.parse(message);
	console.log(channel + ':' + message.event, message.data);
	io.to(message.event).emit('chat', message.data);
});