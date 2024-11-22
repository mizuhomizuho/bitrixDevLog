# bitrixDevLog

```php
(new \Ms\General\Site\Log\Dev(
    'INFO__yaPay__' . __FUNCTION__ . '__start' // можно __FILE__
))->add(
    [
        __FILE__,
        __LINE__,
        'userId' => \Bitrix\Main\Engine\CurrentUser::get()->getId(),
        '$request' => $request,
        '$_SERVER' => $_SERVER,
        'debug_backtrace' => \Ms\General\Site\Log\Dev::getDebugBacktracePrint(),
    ],
    'your@email.meow',
);
```

Here's another example:

```php
$devLog = (new \Ms\General\Site\Log\Dev())
    ->setLogFile(__FILE__) // It turns out '/local/logs/' . $logFile . '.log'
    ->add(
        [
            'test' => 'xxx',
        ],
        // 'your@email.meow', // Default alert frequency is once a day
    );
```
