<?php


namespace JStormes\AWSwrapper;


class LoginFormatExample implements FormatterInterface
{
    public function isCogent($severity, $msg, $context): bool {

        if ($severity==='login')
            return true;
        return false;
    }

    public function format($severity, $msg, $context) : string {

        $payload= [
            'severity' => $severity,
            'msg' => $msg,
            'context' => $context
        ];

        return json_encode($payload, JSON_PRETTY_PRINT);

    }
}