# Control LG WebOS TV with Laravel

## Installation

```
composer require flexnst/laravel-lgtv
```

```
php artisan vendor:publish --provider="Flexnst\LgTv\LgTvServiceProvider" --tag=config
```

## Usage examples:

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