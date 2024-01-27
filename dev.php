<?php

namespace Ms\General\Site\Log;

use Bitrix\Main\Type\DateTime;

class Dev {

    private string $logFile;
    private string $maxLive;
    private int $emailTimeout;

    const MAX_LIVE = '3 months';
    const EMAIL_TIMEOUT = 86400;
    const AGENT_SEND = 'SN';
    const AGENT_DELETE = 'DL';

    function __construct(

        string $logFile = __FILE__,
        string $maxLive = self::MAX_LIVE,
        string $emailTimeout = self::EMAIL_TIMEOUT,

    ) {
        $this->setLogFile($logFile);
        $this->setMaxLive($maxLive);
        $this->setEmailTimeout($emailTimeout);
    }

    function setMaxLive(

        string $maxLive,

    ): self {

        $this->maxLive = $maxLive;

        return $this;
    }

    function getMaxLive(): string {

        return $this->maxLive;
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

    static function getDebugBacktracePrint(): string {

        ob_start();
        debug_print_backtrace();
        return (string) ob_get_clean();
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

        $maxLiveTime = new DateTime();
        $maxLiveTime->add('-' . $this->getMaxLive());

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

                eval(
                    '$sendedAgentParams = ' . explode($agentFunc, $sendedAgent['NAME'])[1]
                );

                if ($sendedAgentParams['status'] !== static::AGENT_SEND) {

                    \CAgent::Update($sendedAgent['ID'], [
                        'NAME' => sprintf($agentFuncTpl, var_export($agentFuncParams, true)),
                    ]);
                }
            }
        }

        $logArr = [];

        $getLogStrFns = function (array $logVar, float $microtime): string {
            return '$arr[\'' . $microtime . '\'][] = '
                . var_export($logVar, true)
                . ";\n";
        };

        $varJson = json_encode(

            $var,

            JSON_UNESCAPED_UNICODE
            |JSON_UNESCAPED_SLASHES
            |JSON_PRETTY_PRINT
        );
        $varHash = md5($varJson);

        if (file_exists($logFile)) {

            $arr = [];
            eval(file_get_contents($logFile));

            foreach ($arr as $msgMicrotime => $arrVal) {
                foreach ($arrVal as $msgKey => $msgVar) {
                    if ((float) $msgMicrotime > $maxLiveTime->getTimestamp()) {

                        if ($varHash === $msgVar['hash']) {
                            unset($msgVar['var']);
                        }

                        $logArr[$msgMicrotime][] = $getLogStrFns($msgVar, $msgMicrotime);
                    }
                }
            }
        }
        else {
            $logDir = dirname($logFile);
            if (!file_exists($logDir)) {
                mkdir($logDir, 0777, true);
            }
        }

        $logArr[(string) $microtime][] = $getLogStrFns([
            'time' => $nowTime->format('Y-m-d H:i:s'),
            'hash' => $varHash,
            'var' => $varJson,
        ], $microtime);

        krsort($logArr);

        $res = [];
        foreach ($logArr as $logArrVal) {
            foreach ($logArrVal as $msgVar) {
                $res[] = $msgVar;
            }
        }

        file_put_contents(
            $logFile,
            implode('//' . str_repeat('*', 88) . "\n", $res)
        );

        return $this;
    }
}