# Control LG WebOS TV with Laravel

## Installation

```
composer require flexnst/laravel-lgtv
```

```
php artisan vendor:publish --provider="Flexnst\LgTv\LgTvServiceProvider" --tag=config
```

## Usage examples

Discover TV ip address on network:
```
$ip = \LgTv::device()->discover();
```

Start TV:
```
\LgTv::device()->turn_on();
```

TV off:
```
\LgTv::device()->turn_off();
```

Show float message on TV screen:
```
\LgTv::device()->show_float('Hello!');
```

## Support many devices

```php
// config/lgtv.php
<?php

return [
    'devices' => [
        'tv1' => [
            'ip' => env('LGTV_TV1_IP'),
            'mac' => env('LGTV_TV1_MAC'),
            'key_path' => storage_path('lgtv_tv1.key')
        ]
    ],
    'default' => 'tv1'
];
```

## Available methods

- discover(bool $as_array = false)
- turn_on()
- show_float(string $message)
- open_browser_at(string $url)
- get_mute()
- set_mute(bool $status)
- toggle_mute()
- channels($hidden = false)
- get_channel()
- set_channel(string $channelId)
- get_volume()
- set_volume(int $volumelevel)
- input_play()
- input_pause()
- input_stop()
- input_forward()
- input_rewind()
- input_enter()
- input_channel_up()
- input_channel_down()
- input_volume_up()
- input_volume_down()
- input_backspace(int $countCharacters)
- input_three_d_on()
- input_three_d_off()
- get_audio_status()
- get_sw_info()
- get_services()
- get_apps()
- open_app_with_payload(array $payload)
- start_app(string $appid)
- close_app(string $appid)
- open_youtube_by_url(string $url)
- open_youtube_by_id(string $id)
- button(string $button)