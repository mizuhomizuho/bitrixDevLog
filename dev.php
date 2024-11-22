<?php

namespace Ms\General\Site\Log;

use Bitrix\Main\Type\DateTime;

class Dev {

    private string $logFile;
    private int $emailTimeout;

    const MAX_LIVE = '3 months';
    const EMAIL_TIMEOUT = 86400;
    const AGENT_SEND = 'SN';
    const AGENT_DELETE = 'DL';

    function __construct(

        string $logFile = __FILE__,
        string $emailTimeout = self::EMAIL_TIMEOUT,

    ) {
        $this->setLogFile($logFile);
        $this->setEmailTimeout($emailTimeout);
    }

    function setEmailTimeout(

        int $emailTimeout,

    ): self {

        $this->emailTimeout = $emailTimeout;

        return $this;
    }

    function getEmailTimeout(): int {

        return $this->emailTimeout ;
    }

    static function getDebugBacktracePrint(): array {

        ob_start();
        debug_print_backtrace();
        $res = (array) preg_split('/\n/', (string) ob_get_clean());
        unset($res[count($res)-1]);
        return $res;
    }

    function setLogFile(

        string $logFile = __FILE__,

    ): self {

        $logFile = $logFile . '.log';

        if (mb_strpos($logFile, $_SERVER['DOCUMENT_ROOT']) === 0) {
            $logFile = mb_substr($logFile, mb_strlen($_SERVER['DOCUMENT_ROOT']));
        }

        $logFile = preg_replace('/^\//', '', $logFile);

        $this->logFile = $_SERVER['DOCUMENT_ROOT'] . '/local/logs/' . $logFile;

        return $this;
    }

    function getLogFile(): string {

        return $this->logFile;
    }

    static function agentDevEmail(

        array $params,

    ): string {

        if ($params['status'] !== static::AGENT_SEND) {

            return '';
        }

        if (file_exists($params['logFile'])){
            bxmail(
                $params['emailTo'],
                '!!! ВНИМАНИЕ !!! Обновление лога ' . basename($params['logFile']) . ' !!!',
                $params['logFile'],
            );
        }

        $agentFuncTpl = __CLASS__ . '::' . __FUNCTION__ . '(%s);';

        $params['status'] = static::AGENT_DELETE;

        return sprintf($agentFuncTpl, var_export($params, true));
    }

    function add(

        mixed $var,
        string $emailTo = '',

    ): self {

        $logFile = $this->getLogFile();

        $microtime = microtime(true);

        $nowTime = new DateTime();

        if ($emailTo) {

            $agentFunc = __CLASS__ . '::agentDevEmail';
            $agentFuncTpl = $agentFunc . '(%s);';

            $agentFuncParams = [
                'emailTo' => $emailTo,
                'logFile' => $logFile,
                'emailTimeout' => $this->getEmailTimeout(),
                'status' => '__',
            ];

            $sendedAgent = \CAgent::getList([], [
                'NAME' => sprintf($agentFuncTpl, var_export($agentFuncParams, true)),
            ])->Fetch();

            $agentFuncParams['status'] = static::AGENT_SEND;

            if (!$sendedAgent) {

                \CAgent::AddAgent(
                    sprintf($agentFuncTpl, var_export($agentFuncParams, true)),
                    '',
                    'N',
                    $this->getEmailTimeout(),
                );
            }
            else {

                $sendedAgentParams = null;

                try {
                    eval(
                        '$sendedAgentParams = ' . explode($agentFunc, $sendedAgent['NAME'])[1]
                    );
                } catch (\ParseError $e) {}

                if ($sendedAgentParams['status'] !== static::AGENT_SEND) {

                    \CAgent::Update($sendedAgent['ID'], [
                        'NAME' => sprintf($agentFuncTpl, var_export($agentFuncParams, true)),
                    ]);
                }
            }
        }

        if (!file_exists($logFile)) {
            $logDir = dirname($logFile);
            if (!file_exists($logDir)) {
                mkdir($logDir, 0777, true);
            }
        }

        file_put_contents(
            $logFile,
            '//' . str_repeat('*', 88) . "\n" . '$arr[\'' . $microtime . '\'][] = '
            . var_export([
                'time' => $nowTime->format('Y-m-d H:i:s'),
                'var' => json_encode($var,
                    JSON_UNESCAPED_UNICODE
                    |JSON_UNESCAPED_SLASHES
                    |JSON_PRETTY_PRINT
                ),
            ], true)
            . ";\n",
            FILE_APPEND
        );

        return $this;
    }
}
