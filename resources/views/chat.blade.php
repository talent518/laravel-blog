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
