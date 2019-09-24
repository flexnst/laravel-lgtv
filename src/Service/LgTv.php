<?php


namespace Flexnst\LgTv\Service;


use WebSocket\Client;

class LgTv
{
    public const INPUT_TYPE_CLICK = 'click';
    public const INPUT_TYPE_BUTTON = 'button';
    public const INPUT_TYPE_MOVE = 'move';
    public const INPUT_TYPE_SCROLL = 'scroll';

    public const BTN_UP = 'UP';
    public const BTN_DOWN = 'DOWN';
    public const BTN_LEFT = 'LEFT';
    public const BTN_RIGHT = 'RIGHT';
    public const BTN_BACK = 'BACK';
    public const BTN_HOME = 'HOME';
    public const BTN_ENTER = 'ENTER';
    public const BTN_EXIT = 'EXIT';

    public const INPUT_GROUP_BTN = [
        self::BTN_UP,
        self::BTN_DOWN,
        self::BTN_LEFT,
        self::BTN_RIGHT,
        self::BTN_BACK,
        self::BTN_HOME,
        self::BTN_ENTER,
        self::BTN_EXIT,
    ];

    /**
     * @var string
     */
    protected $ip;

    /**
     * @var string
     */
    protected $mac;

    /**
     * @var string
     */
    protected $clientKey;

    /**
     * @var string
     */
    protected $clientKeyFilename;

    /**
     * @var string
     */
    protected $device;

    public static function device(string $device = null): self
    {
        return new self($device);
    }

    public function __construct($device = null)
    {
        $this->device = $device ?? config('lgtv.default');
    }

    protected function command(string $uri, $payload = null)
    {
        $webSocketClient = new LgTvWebSocket($this->device);
        return $webSocketClient->connect()->sendCommand($uri, $payload);
    }

    protected function input(array $payload)
    {
        $response = $this->command('ssap://com.webos.service.networkinput/getPointerInputSocket');
        $uri = data_get($response, 'payload.socketPath');
        $client = new Client($uri);
        $payload = collect($payload)
                ->transform(function($value, $key){
                    return $key . ':' . $value;
                })
                ->implode(PHP_EOL)
                . str_repeat(PHP_EOL, 2);

        $client->send($payload);
        $client->close();
    }

    public function discover(bool $as_array = false)
    {
        $ip = "239.255.255.250";
        $port = 1900;
        $str = "M-SEARCH * HTTP/1.1\r\n";
        $str .= "HOST: 239.255.255.250:1900\r\n";
        $str .= "MAN: \"ssdp:discover\"\r\n";
        $str .= "MX: 5\r\n";
        $str .= "ST: urn:dial-multiscreen-org:service:dial:1\r\n";
        $str .= "USER-AGENT: iOS/5.0 UDAP/2.0 iPhone/4\r\n\r\n";

        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);
        socket_set_option($sock, SOL_SOCKET, SO_RCVTIMEO, ["sec" => 5, "usec" => 0]);
        socket_sendto($sock, $str, strlen($str), 0, $ip, 1900);

        while (true) {
            $ret = @socket_recvfrom($sock, $buf, 2048, 0, $ip, $port);

            if ($ret === false) {
                break;
            }

            $headers = [];

            if (preg_match_all("/^([^\n]+): ([^\n]+)?$/sim", $buf, $o, PREG_SET_ORDER)) {
                foreach ($o as $match) {
                    $headers[$match[1]] = trim($match[2], "\r");
                }

                $out = [
                    'headers' => $headers,
                    'ip' => $ip,
                    'port' => $port
                ];
            } else {
                $out = false;
            }
        }

        socket_close($sock);

        if (!$as_array) {
            $out = $out['ip'] ?? false;
        }

        return $out ?? false;
    }

    public function turn_on(): self
    {
        $mac = config("lgtv.devices.{$this->device}.mac");
        $hwaddr = pack('H*', preg_replace('/[^0-9a-fA-F]/', '', $mac));

        // Create Magic Packet
        $packet = sprintf(
            '%s%s',
            str_repeat(chr(255), 6),
            str_repeat($hwaddr, 16)
        );

        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if ($sock !== false) {
            $options = socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, true);

            if ($options !== false) {
                socket_sendto($sock, $packet, strlen($packet), 0, '255.255.255.255', 7);
                socket_close($sock);
            }
        }

        sleep(3);

        return $this;
    }

    public function turn_off()
    {
        return $this->command("ssap://system/turnOff");
    }

    public function show_float(string $message)
    {
        return $this->command("ssap://system.notifications/createToast", ['message' => $message]);
    }

    public function open_browser_at(string $url)
    {
        return $this->command("ssap://system.launcher/open", ['target' => $url]);
    }

    public function get_mute(): bool
    {
        $response = $this->command("ssap://audio/getStatus");

        return data_get($response, 'payload.mute', null);
    }

    public function set_mute(bool $status)
    {
        return $this->command("ssap://audio/setMute", [
            'mute' => $status
        ]);
    }

    public function toggle_mute()
    {
        return $this->set_mute(!$this->get_mute());
    }

    public function channels($hidden = false): array
    {
        $response = $this->command("ssap://tv/getChannelList");

        $list = collect(data_get($response, 'payload.channelList', []));

        if (!$hidden) {
            $list = $list->filter(function ($channel) use ($hidden) {
                return (bool)$channel->display;
            });
        }

        return $list->map(function ($channel) {
            return [
                'id' => $channel->channelId,
                'number' => $channel->channelNumber,
                'name' => $channel->channelName,
                //'logo' => $channel->imgUrl,
            ];
        })
            ->toArray();
    }

    public function get_channel(): array
    {
        $response = $this->command("ssap://tv/getCurrentChannel");
        return [
            'id' => data_get($response, 'payload.channelId', null),
            'name' => data_get($response, 'payload.channelName', null),
            'number' => data_get($response, 'payload.channelNumber', null),
        ];
    }

    public function set_channel(string $channelId)
    {
        return $this->command("ssap://tv/openChannel", ['channelId' => $channelId]);
    }

    public function get_volume(): ?int
    {
        $response = $this->command("ssap://audio/getVolume");

        if (!data_get($response, 'payload.muted', false)) {
            return data_get($response, 'payload.volume', null);
        }

        return -1;
    }

    public function set_volume(int $volumelevel)
    {
        if ($volumelevel < 0 || $volumelevel > 100) {
            throw new \Exception('volume must be 0..100');
        }

        return $this->command("ssap://audio/setVolume", ['volume' => $volumelevel]);
    }

    public function input_play()
    {
        return $this->command("ssap://media.controls/play");
    }

    public function input_pause()
    {
        return $this->command("ssap://media.controls/pause");
    }

    public function input_stop()
    {
        return $this->command("ssap://media.controls/stop");
    }

    public function input_forward()
    {
        return $this->command("ssap://media.controls/fastForward");
    }

    public function input_rewind()
    {
        return $this->command("ssap://media.controls/rewind");
    }

    public function input_enter()
    {
        return $this->command("ssap://com.webos.service.ime/sendEnterKey");
    }

    public function input_channel_up()
    {
        return $this->command("ssap://tv/channelUp");
    }

    public function input_channel_down()
    {
        return $this->command("ssap://tv/channelDown");
    }

    public function input_volume_up()
    {
        return $this->command("ssap://audio/volumeUp");
    }

    public function input_volume_down()
    {
        return $this->command("ssap://audio/volumeDown");
    }

    public function input_backspace(int $countCharacters)
    {
        return $this->command("ssap://com.webos.service.ime/deleteCharacters", ['count' => $countCharacters]);
    }

    public function input_three_d_on()
    {
        return $this->command("ssap://com.webos.service.tv.display/set3DOn");
    }

    public function input_three_d_off()
    {
        return $this->command("ssap://com.webos.service.tv.display/set3DOff");
    }

    public function get_audio_status()
    {
        return $this->command("ssap://audio/getStatus");
    }

    public function get_sw_info()
    {
        return $this->command("ssap://com.webos.service.update/getCurrentSWInformation");
    }

    public function get_services()
    {
        $response = $this->command("ssap://api/getServiceList");
        return data_get($response, 'payload.services', []);
    }

    public function get_apps()
    {
        $response = $this->command("ssap://com.webos.applicationManager/listLaunchPoints");
        $applist = [];
        $launchpoints = data_get($response, 'payload.launchPoints');
        foreach ($launchpoints as $launchpoint) {
            $applist[$launchpoint->title] = $launchpoint->launchPointId;
        }
        return $applist;
    }

    public function open_app_with_payload(array $payload)
    {
        return $this->command("ssap://com.webos.applicationManager/launch", $payload);
    }

    public function start_app(string $appid)
    {
        $response = $this->command("ssap://system.launcher/launch", ['id' => $appid]);

        $this->checkError($response);

        return data_get($response, 'payload.sessionId');
    }

    public function close_app(string $appid)
    {
        $response = $this->command("ssap://system.launcher/close", ['id' => $appid]);

        $this->checkError($response);

        return data_get($response, 'payload.sessionId');
    }

    public function open_youtube_by_url(string $url)
    {
        $response = $this->command("ssap://system.launcher/launch", [
            'id' => 'youtube.leanback.v4',
            'params' => [
                'contentTarget' => $url
            ]
        ]);

        $this->checkError($response);

        return data_get($response, 'payload.sessionId');
    }

    public function open_youtube_by_id(string $id)
    {
        return $this->open_youtube_by_url("http://www.youtube.com/tv?v={$id}");
    }

    public function button(string $button)
    {
        if(!in_array($button, self::INPUT_GROUP_BTN, true)){
            throw new \Exception('Undefined button code: ' . $button);
        }

        $this->input([
            'type' => self::INPUT_TYPE_BUTTON,
            'name' => $button
        ]);
    }

    public function click()
    {
        $this->input([
            'type' => self::INPUT_TYPE_CLICK
        ]);
    }

    public function move(int $dx, int $dy, bool $drag = false)
    {
        $this->input([
            'type' => self::INPUT_TYPE_MOVE,
            'dx' => $dx,
            'dy' => $dy,
            'down' => (int)$drag
        ]);
    }

    public function scroll(int $dx, int $dy)
    {
        $this->input([
            'type' => self::INPUT_TYPE_SCROLL,
            'dx' => $dx,
            'dy' => $dy,
        ]);
    }

    protected function checkError($response)
    {
        if ($errorCode = data_get($response, 'payload.errorCode')) {
            $code = data_get($response, 'payload.errorCode');
            $text = data_get($response, 'payload.errorText');
            throw new \Exception("LGTV error [{$code}]: {$text}");
        }
    }
}
