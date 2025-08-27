<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Laravel Reverb WebSocket Test</title>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.3.0/dist/web/pusher.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 2rem;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .status {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .connected { background-color: #4CAF50; }
        .disconnected { background-color: #f44336; }
        .connecting { background-color: #FFC107; }
        h1 { color: #333; }
        .messages {
            height: 300px;
            overflow-y: auto;
            border: 1px solid #eee;
            padding: 1rem;
            background-color: #f9f9f9;
            border-radius: 4px;
        }
        .message {
            margin-bottom: 0.5rem;
            padding: 0.5rem;
            border-bottom: 1px solid #eee;
        }
        .timestamp {
            color: #777;
            font-size: 0.8rem;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 8px;
        }
        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }
        button.disconnect {
            background-color: #f44336;
        }
        button.trigger {
            background-color: #2196F3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Laravel Reverb WebSocket Test</h1>
            <div class="status">
                <div id="statusIndicator" class="status-indicator disconnected"></div>
                <span id="statusText">Disconnected</span>
            </div>
            <div>
                <button id="connectBtn">Connect</button>
                <button id="disconnectBtn" class="disconnect" disabled>Disconnect</button>
                <button id="triggerBtn" class="trigger">Trigger Test Event</button>
            </div>
        </div>

        <div class="card">
            <h2>Event Messages</h2>
            <div id="messages" class="messages"></div>
        </div>
        
        <div class="card">
            <h2>Connection Debug Info</h2>
            <div class="debug-info" style="background: #f5f5f5; padding: 1rem; border-radius: 4px; font-family: monospace; overflow: auto;">
                <p><strong>Host:</strong> <span id="debugHost"></span></p>
                <p><strong>Port:</strong> <span id="debugPort"></span></p>
                <p><strong>App Key:</strong> <span id="debugKey"></span></p>
                <p><strong>Transport:</strong> <span id="debugTransport"></span></p>
                <p><strong>Socket ID:</strong> <span id="debugSocketId">Not connected</span></p>
                <p><strong>Connection State:</strong> <span id="debugState">Not connected</span></p>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusIndicator = document.getElementById('statusIndicator');
            const statusText = document.getElementById('statusText');
            const connectBtn = document.getElementById('connectBtn');
            const disconnectBtn = document.getElementById('disconnectBtn');
            const triggerBtn = document.getElementById('triggerBtn');
            const messagesContainer = document.getElementById('messages');
            const debugHost = document.getElementById('debugHost');
            const debugPort = document.getElementById('debugPort');
            const debugKey = document.getElementById('debugKey');
            const debugTransport = document.getElementById('debugTransport');
            const debugSocketId = document.getElementById('debugSocketId');
            const debugState = document.getElementById('debugState');
            
            let echoInstance = null;

            function updateStatus(status) {
                statusText.textContent = status;
                statusIndicator.className = 'status-indicator ' + 
                    (status === 'Connected' ? 'connected' : 
                     status === 'Connecting...' ? 'connecting' : 'disconnected');
                
                connectBtn.disabled = status !== 'Disconnected';
                disconnectBtn.disabled = status === 'Disconnected';
            }

            function addMessage(message) {
                const messageElement = document.createElement('div');
                messageElement.className = 'message';
                
                const timestamp = document.createElement('span');
                timestamp.className = 'timestamp';
                timestamp.textContent = new Date().toLocaleTimeString() + ' - ';
                
                messageElement.appendChild(timestamp);
                messageElement.appendChild(document.createTextNode(message));
                
                messagesContainer.appendChild(messageElement);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }

            function connectToReverb() {
                try {
                    updateStatus('Connecting...');
                    
                    // Gather connection details for debugging
                    const host = window.location.hostname;
                    const port = {{ env('REVERB_SERVER_PORT', 8090) }};
                    const appKey = '{{ env("REVERB_APP_KEY", "ABCD12345") }}';
                    
                    // Update debug display
                    debugHost.textContent = host;
                    debugPort.textContent = port;
                    debugKey.textContent = appKey;
                    debugTransport.textContent = 'ws/wss';
                    
                    // Log connection details to console
                    console.log('Attempting to connect to Reverb WebSocket', {
                        host,
                        port,
                        appKey,
                        url: `${window.location.protocol === 'https:' ? 'wss' : 'ws'}://${host}:${port}/app/${appKey}`
                    });
                    
                    addMessage(`Initializing connection to Reverb WebSocket server at ${host}:${port} with key ${appKey}...`);

                    echoInstance = new Echo({
                        broadcaster: 'pusher',
                        key: appKey,
                        wsHost: host,
                        wsPort: port,
                        forceTLS: false,
                        disableStats: true,
                        enabledTransports: ['ws', 'wss'],
                        // Add debug mode to see detailed logs in console
                        enabledTransports: ['ws', 'wss']
                    });
                    
                    // Log the Echo instance for debugging
                    console.log('Echo instance created:', echoInstance);

                    echoInstance.connector.pusher.connection.bind('connected', () => {
                        console.log('Connected to Reverb WebSocket server');
                        updateStatus('Connected');
                        addMessage('Successfully connected to Reverb WebSocket server');
                        
                        // Update debug info
                        debugSocketId.textContent = echoInstance.socketId();
                        debugState.textContent = 'connected';
                    });

                    echoInstance.connector.pusher.connection.bind('disconnected', () => {
                        console.log('Disconnected from Reverb WebSocket server');
                        updateStatus('Disconnected');
                        addMessage('Disconnected from Reverb WebSocket server');
                        
                        // Update debug info
                        debugSocketId.textContent = 'Not connected';
                        debugState.textContent = 'disconnected';
                    });
                    
                    echoInstance.connector.pusher.connection.bind('connecting', () => {
                        console.log('Connecting to Reverb WebSocket server...');
                        updateStatus('Connecting...');
                        addMessage('Connecting to Reverb WebSocket server...');
                        
                        // Update debug info
                        debugState.textContent = 'connecting';
                    });

                    echoInstance.connector.pusher.connection.bind('error', (error) => {
                        console.error('Connection error:', error);
                        addMessage(`Connection error: ${JSON.stringify(error)}`);
                        updateStatus('Disconnected');
                        
                        // Update debug info
                        debugSocketId.textContent = 'Not connected';
                        debugState.textContent = 'error';
                    });
                    
                    // Add more detailed error handling
                    echoInstance.connector.pusher.connection.bind('failed', () => {
                        console.error('Connection failed');
                        addMessage('Connection failed - check the console for more details');
                        updateStatus('Disconnected');
                        
                        // Update debug info
                        debugState.textContent = 'failed';
                    });

                    addMessage('Subscribing to "test-channel"...');
                    console.log('Subscribing to test-channel');
                    
                    try {
                        echoInstance.channel('test-channel')
                            .listen('.App\Events\TestEvent', (data) => {
                                console.log('Received event on test-channel:', data);
                                addMessage(`Received TestEvent: ${JSON.stringify(data)}`);
                            });
                    } catch (channelError) {
                        console.error('Error subscribing to channel:', channelError);
                        addMessage(`Error subscribing to channel: ${channelError.message || channelError}`);
                    }
                } catch (error) {
                    console.error('Connection error details:', error);
                    addMessage(`Error connecting: ${error.message || error}`);
                    
                    // Try to provide more detailed error info
                    if (error.stack) {
                        console.error('Stack trace:', error.stack);
                    }
                    
                    updateStatus('Disconnected');
                    
                    // Update debug info
                    debugSocketId.textContent = 'Error connecting';
                    debugState.textContent = 'error';
                }
            }

            function disconnectFromReverb() {
                if (echoInstance) {
                    echoInstance.disconnect();
                    echoInstance = null;
                    addMessage('Manually disconnected from Reverb WebSocket server');
                    updateStatus('Disconnected');
                }
            }

            function triggerTestEvent() {
                addMessage('Triggering TestEvent via API...');
                
                fetch('/broadcast-test')
                    .then(response => response.text())
                    .then(text => {
                        addMessage(`Event triggered: ${text}`);
                    })
                    .catch(error => {
                        addMessage(`Error triggering event: ${error.message}`);
                    });
            }

            connectBtn.addEventListener('click', connectToReverb);
            disconnectBtn.addEventListener('click', disconnectFromReverb);
            triggerBtn.addEventListener('click', triggerTestEvent);
        });
    </script>
</body>
</html>

