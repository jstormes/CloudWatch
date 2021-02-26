<?php


namespace JStormes\AWSwrapper;


interface FormatterInterface
{
    public function isCogent($severity, $msg, $context): bool;
    public function format($severity, $msg, $context) : string;
}