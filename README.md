# bitrixDevLog

Для отладки кода использую вот такой класс.
Ставлю его на возможные ошибки и в случае
срабатывания происходит запись в лог и отправка
сообщения на почту (с таймаутом, так что сообщениями
не завали). Еще он сортирует записи в
обратном порядке (новые сверху) и есть лимит времени
хранения сообщений. Еще их удобно подключать inclue`ом для
анализа. Так что Ваш лог никогда не переполница и сообщит
о своем существовании на почту =). Складывает он все
в папку local/logs.

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
        'debug_backtrace' => debug_backtrace(),
    ],
    'your@email.meow',
);
```

Вот еще пример:

```php
$devLog = (new \Ms\General\Site\Log\Dev())
    ->setLogFile(__FILE__) // Получается '/local/logs/' . $logFile . '.log'
    ->setMaxLive('1 month') // Хранить сообщения в логе не старше 1 месяца
    ->add(
        [
            'test' => 'xxx',
        ],
        // 'your@email.meow', // Частота оповещений по умолчанию 1 раз в день
    );

$logFile = $devLog->getLogFile();
$maxLive = $devLog->getMaxLive();
```