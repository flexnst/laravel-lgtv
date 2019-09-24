<?php


namespace Flexnst\Lgtv\Service;


use WebSocket\Client;

class LgTvWebSocket
{
    /**
     * @var string
     */
    protected $device;

    /**
     * @var string|null
     */
    protected $clientKey;

    /**
     * @var Client
     */
    protected $wsclient;

    /**
     * @var bool
     */
    protected $handshake = false;

    /**
     * Commands counter
     *
     * @var int
     */
    protected $commandId = 0;

    public function __construct($device)
    {
        $this->device = $device;
        $keypath = config("lgtv.devices.{$this->device}.key_path");
        if(file_exists($keypath)){
            $this->clientKey = file_get_contents($keypath);
        } else {
            file_put_contents($keypath, '');
        }
    }

    protected function getUri()
    {
        $ip = config("lgtv.devices.{$this->device}.ip");
        return "ws://{$ip}:3000";
    }

    protected function getClient()
    {
        return $this->wsclient = $this->wsclient ?? new Client($this->getUri());
    }

    protected function handshake(){

        $handshake = [
            'type' => 'register',
            'id' => 'register_0',
            'payload' => [
                'forcePairing' => false,
                'pairingType' => 'PROMPT',
                'manifest' => [
                    'manifestVersion' => 1,
                    'appVersion' => '1.1',
                    'signed' => [
                        'created' => '20140509',
                        'appId' => 'com.lge.test',
                        'vendorId' => 'com.lge',
                        'localizedAppNames' => [
                            '' => 'LG Remote App',
                            'ko-KR' => '리모컨 앱',
                            'zxx-XX' => 'ЛГ Rэмotэ AПП',
                        ],
                        'localizedVendorNames' => [
                            '' => 'LG Electronics'
                        ],
                        'permissions' => [
                            'TEST_SECURE',
                            'CONTROL_INPUT_TEXT',
                            'CONTROL_MOUSE_AND_KEYBOARD',
                            'READ_INSTALLED_APPS',
                            'READ_LGE_SDX',
                            'READ_NOTIFICATIONS',
                            'SEARCH',
                            'WRITE_SETTINGS',
                            'WRITE_NOTIFICATION_ALERT',
                            'CONTROL_POWER',
                            'READ_CURRENT_CHANNEL',
                            'READ_RUNNING_APPS',
                            'READ_UPDATE_INFO',
                            'UPDATE_FROM_REMOTE_APP',
                            'READ_LGE_TV_INPUT_EVENTS',
                            'READ_TV_CURRENT_TIME',
                        ],
                        'serial' => '2f930e2d2cfe083771f68e4fe7bb07',
                    ],
                    'permissions' => [
                        "LAUNCH",
                        "LAUNCH_WEBAPP",
                        "APP_TO_APP",
                        "CLOSE",
                        "TEST_OPEN",
                        "TEST_PROTECTED",
                        "CONTROL_AUDIO",
                        "CONTROL_DISPLAY",
                        "CONTROL_INPUT_JOYSTICK",
                        "CONTROL_INPUT_MEDIA_RECORDING",
                        "CONTROL_INPUT_MEDIA_PLAYBACK",
                        "CONTROL_INPUT_TV",
                        "CONTROL_POWER",
                        "READ_APP_STATUS",
                        "READ_CURRENT_CHANNEL",
                        "READ_INPUT_DEVICE_LIST",
                        "READ_NETWORK_STATE",
                        "READ_RUNNING_APPS",
                        "READ_TV_CHANNEL_LIST",
                        "WRITE_NOTIFICATION_TOAST",
                        "READ_POWER_STATE",
                        "READ_COUNTRY_INFO"
                    ],
                    'signatures' => [[
                        'signatureVersion' => 1,
                        'signature' => 'eyJhbGdvcml0aG0iOiJSU0EtU0hBMjU2Iiwia2V5SWQiOiJ0ZXN0LXNpZ25pbmctY2VydCIsInNpZ25hdHVyZVZlcnNpb24iOjF9.hrVRgjCwXVvE2OOSpDZ58hR+59aFNwYDyjQgKk3auukd7pcegmE2CzPCa0bJ0ZsRAcKkCTJrWo5iDzNhMBWRyaMOv5zWSrthlf7G128qvIlpMT0YNY+n/FaOHE73uLrS/g7swl3/qH/BGFG2Hu4RlL48eb3lLKqTt2xKHdCs6Cd4RMfJPYnzgvI4BNrFUKsjkcu+WD4OO2A27Pq1n50cMchmcaXadJhGrOqH5YmHdOCj5NSHzJYrsW0HPlpuAx/ECMeIZYDh6RMqaFM2DXzdKX9NmmyqzJ3o/0lkk/N97gfVRLW5hA29yeAwaCViZNCP8iC9aO0q9fQojoa7NQnAtw=='
                    ]]
                ]
            ]
        ];
        if($this->clientKey) {
            $handshake['payload']['client-key'] = $this->clientKey;
        }

        return json_encode($handshake, JSON_UNESCAPED_UNICODE);

    }

    protected function saveClientKey(string $clientKey){
        file_put_contents(config("lgtv.devices.{$this->device}.key_path"), $clientKey);
    }

    protected function prepareCommand(string $uri, $payload = null)
    {
        return json_encode([
            'id' => ++$this->commandId,
            'type' => 'request',
            'uri' => $uri,
            'payload' => $payload
        ]);
    }

    protected function prepareResponse(string $response)
    {
        return json_decode($response);
    }

    public function connect()
    {
        if(!$this->handshake){
            $client = $this->getClient();
            $client->setTimeout(10);
            $client->send($this->handshake());
            if(!$this->clientKey){
                sleep(8);
                $client->receive();
            }
            $response = json_decode($client->receive());
            if(isset($response->payload->{'client-key'})) {
                $this->handshake = true;
                $this->saveClientKey($response->payload->{'client-key'});
            }
        }
        return $this;
    }

    public function sendCommand(string $uri, $payload = null)
    {
        $client = $this->getClient();
        $client->setTimeout(2);
        $client->send($this->prepareCommand($uri, $payload));
        sleep(1);
        return $this->prepareResponse($client->receive());
    }
}
