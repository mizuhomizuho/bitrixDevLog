# bitrixDevLog

Для отлидки кода использую вот такой класс.
Ставлю его на возможные ошибки и в случае
срабатывания происходит запись в лог и отправка
сообщения на почту. Еще он сортирует сообщения в
обратном порядке и есть лимит времени хранения
сообщений. Еще их удобно подключать inclue`ом для
анализа. Так что лог никогда не переполница и сообщим
о своем существовании на почту =)

```php
(new \Ms\General\Site\Log\Dev(
    'INFO__yaPay__' . __FUNCTION__ . '__start'
))->add(
    [
        __FILE__,
        __LINE__,
        '$userId' => \Bitrix\Main\Engine\CurrentUser::get()->getId(),
        '$request' => $request,
        '$_SERVER' => $_SERVER,
        'debug_backtrace' => debug_backtrace(),
    ],
    'your@email.meow',
);
```