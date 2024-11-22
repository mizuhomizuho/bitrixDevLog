# bitrixDevLog

To debug code I use this class. I set it up for
possible errors and if it is triggered, it is 
written to the log and a message is sent by email
(with a timeout, so it’s not flooded with messages). It is also convenient to connect them inclue for analysis. It puts everything in the local/logs folder.

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
