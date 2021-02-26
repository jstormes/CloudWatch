<?php


namespace JStormes\AWSwrapper;

use Aws\CloudWatchLogs\CloudWatchLogsClient;
use Aws\Result;

class Logs
{
    /** @var CloudWatchLogsClient  */
    private $cloudWatchLogsClient;

    /** @var string */
    private $sequenceToken = '0';

    /** @var string  */
    private $logGroupName = '';

    /** @var string  */
    private $logStreamNamePrefix = '';

    /** @var string  */
    private $logGroup = '';

    /** @var string  */
    private $logStreamPrefix = '';

    /** @var string  */
    private $system = '';

    /** @var string  */
    private $application = '';

    /** @var array  */
    private $formatters = [];

    /**
     * Logs constructor.
     * $config = [
     *    'profile' => 'default',
     *    'region' => 'us-west-2',
     *    'version' => 'latest',
     *    'logGroup' => "testGroup",
     *    'logStreamPrefix' => "StreamName",
     *    'system' => 'system',
     *    'application' => str_replace('.php','',basename(__FILE__))
     * ];
     * @param array $config
     * @throws \Exception
     */
    function __construct(array $config) {

        if (isset($config['logStreamPrefix'])){
            $today = date("Y-m-d");
            $this->setLogStream($config['logStreamPrefix']."_".$today);
        }
        else {
            throw new \Exception('"logStreamPrefix" not in config, "logStreamPrefix" is required.');
        }

        if (isset($config['system']))
            $this->setSystem($config['system']);
        else
            throw new \Exception('"system" no set in config, "system" is required.');
        $logGroup = '/'.$this->getSystem();

        if (isset($config['application'])){
            $this->setApplication($config['application']);
            $logGroup .= '/'.$this->getApplication();
        }

        if (isset($config['logGroup']))
            if (strlen($config['logGroup'])>1)
                $logGroup .= "/".$config['logGroup'];
        $this->setLogGroup($logGroup);


        if (isset($config['formatters'])) {
            if (is_array($config['formatters'])) {
                foreach ($config['formatters'] as $formatter) {
                    if (!is_subclass_of($formatter, FormatterInterface::class)) {
                        throw new \Exception('Invalid formatter ', $formatter);
                    }
                }
                $this->formatters= $config['formatters'];
            }
        }

        $this->cloudWatchLogsClient = new CloudWatchLogsClient($config);

        if (!($this->logGroupExists($this->getLogGroup())))
            $this->createLogGroup($this->getLogGroup());

        if (!($this->logStreamExists($this->getLogGroup(),$this->getLogStreamPrefix())))
            $this->createLogStream($this->getLogGroup(),$this->getLogStreamPrefix());

    }

    private function logGroupExists($groupName): bool {

        try {
            $results = $this->cloudWatchLogsClient->describeLogStreams([
                "logGroupName" => $groupName
            ]);
        }
        catch(\Exception $ex) {
            return false;
        }
        return true;
    }

    private function logStreamExists($groupName, $streamName):bool {

        $results = $this->cloudWatchLogsClient->describeLogStreams([
            "logGroupName" => $groupName,
            "logStreamNamePrefix" => $streamName
        ]);

        if (isset($results['logStreams'][0]["logStreamName"]))
            if ($results['logStreams'][0]["logStreamName"]===$streamName) {

                if (isset($results['logStreams'][0]['uploadSequenceToken']))
                    $this->setSequenceToken($results['logStreams'][0]['uploadSequenceToken']);

                return true;
            }

        return false;
    }

    private function createLogGroup($logGroup) {

        $this->cloudWatchLogsClient->createLogGroup([
            'logGroupName' => $logGroup,
        ]);

    }

    private function createLogStream($logGroup, $streamName) {

        $this->cloudWatchLogsClient->createLogStream([
            'logGroupName' => $logGroup,
            'logStreamName' => $streamName
        ]);

    }

    private function log($groupName, $streamName, $message) : Result  {

        $results = $this->cloudWatchLogsClient->putLogEvents(array(

            'logGroupName' => $groupName,
            'logStreamName' => $streamName,
            'logEvents' => array(
                array(
                    'timestamp' => round(microtime(true) * 1000),
                    'message' => $message,
                ),
            ),
            'sequenceToken' => $this->getSequenceToken()
        ));

        $this->setSequenceToken($results['nextSequenceToken']);

        return $results;
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function critical(string $message, array $context= []) {
        $this->logProxy(__FUNCTION__, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function error(string $message, array $context= []) {
        $this->logProxy(__FUNCTION__, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function warning(string $message, array $context= []) {
        $this->logProxy(__FUNCTION__, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function monitor(string $message, array $context= []) {
        $this->logProxy(__FUNCTION__, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function info(string $message, array $context= []) {
        $this->logProxy(__FUNCTION__, $message, $context);
    }

    /**
     * @param string $message
     * @param array $context
     */
    public function debug(string $message, array $context= []) {
        $this->logProxy(__FUNCTION__, $message, $context);
    }

    private function logProxy(string $function, string $message, array $context= []) {

        /** @var FormatterInterface $formatter */
        $formatter=null;
        foreach($this->formatters as $formatter) {
            if ($formatter->isCogent($function,$message, $context)) {
                $this->log(
                    $this->getLogGroup(),
                    $this->getLogStreamPrefix(),
                    $formatter->format($function,$message, $context)
                );
                return;
            }
        }
        $message = $this->buildGenericMessage($function, $message);
        $this->log($this->getLogGroup(), $this->getLogStreamPrefix(), $message);
    }

    public function __call($function, $arg) {
        if (isset($arg)) {
            $message = '';
            $context=[];
            if (isset($arg[0]))
                $message = $arg[0];
            if (isset($arg[1]))
                $context = $arg[1];
            return $this->logProxy($function, $message, $context);
        }

        throw new \Exception('invalid function call: '.$function);
    }

    private function buildGenericMessage($severity, $message) : string {

        $newMessage = "[".$severity."]\n";
        $newMessage .= $this->getSystem()."\n";
        $newMessage .= $this->getApplication()."\n";
        $newMessage .= $message."\n";

        return $newMessage;
    }

    /**
     * @return string
     */
    private function getSequenceToken(): string
    {
        return $this->sequenceToken;
    }

    /**
     * @param string $sequenceToken
     * @return Logs
     */
    private function setSequenceToken(string $sequenceToken): Logs
    {
        $this->sequenceToken = $sequenceToken;
        return $this;
    }

    /**
     * @return string
     */
    private function getLogGroupName(): string
    {
        return $this->logGroupName;
    }

    /**
     * @param string $logGroupName
     * @return Logs
     */
    private function setLogGroupName(string $logGroupName): Logs
    {
        $this->logGroupName = $logGroupName;
        return $this;
    }

    /**
     * @return string
     */
    private function getLogStreamNamePrefix(): string
    {
        return $this->logStreamNamePrefix;
    }

    /**
     * @param string $logStreamNamePrefix
     * @return Logs
     */
    private function setLogStreamNamePrefix(string $logStreamNamePrefix): Logs
    {
        $this->logStreamNamePrefix = $logStreamNamePrefix;
        return $this;
    }

    /**
     * @return string
     */
    private function getLogGroup(): string
    {
        return $this->logGroup;
    }

    /**
     * @param string $logGroup
     * @return Logs
     */
    private function setLogGroup(string $logGroup): Logs
    {
        $this->logGroup = $logGroup;
        return $this;
    }

    /**
     * @return string
     */
    private function getLogStreamPrefix(): string
    {
        return $this->logStreamPrefix;
    }

    /**
     * @param string $logStreamPrefix
     * @return Logs
     */
    private function setLogStream(string $logStreamPrefix): Logs
    {
        $this->logStreamPrefix = $logStreamPrefix;
        return $this;
    }

    /**
     * @return string
     */
    private function getSystem(): string
    {
        return $this->system;
    }

    /**
     * @param string $system
     * @return Logs
     */
    private function setSystem(string $system): Logs
    {
        $this->system = $system;
        return $this;
    }

    /**
     * @return string
     */
    private function getApplication(): string
    {
        return $this->application;
    }

    /**
     * @param string $application
     * @return Logs
     */
    private function setApplication(string $application): Logs
    {
        $this->application = $application;
        return $this;
    }

}