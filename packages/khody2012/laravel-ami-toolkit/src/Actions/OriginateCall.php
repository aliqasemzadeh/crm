<?php

namespace Khody2012\LaravelAmiToolkit\Actions;

use Khody2012\LaravelAmiToolkit\AmiService;
use React\Promise\PromiseInterface;
use InvalidArgumentException;

class OriginateCall
{
    protected AmiService $amiService;
    protected array $params = [];

    public function __construct(AmiService $amiService)
    {
        $this->amiService = $amiService;
    }

    public function setParams(array $params): self
    {
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    public function channel(string $channel): self
    {
        $this->params['Channel'] = $channel;
        return $this;
    }

    public function extension(string $exten, string $context, int $priority = 1): self
    {
        $this->params['Exten']    = $exten;
        $this->params['Context']  = $context;
        $this->params['Priority'] = $priority;
        return $this;
    }

    public function async(bool $async = true): self
    {
        $this->params['Async'] = $async ? 'true' : 'false';
        return $this;
    }

    public function run(bool $fireAndForget = false): PromiseInterface
    {
        $this->validateParams();
        return $this->amiService->originate($this->params, $fireAndForget);
    }

    protected function validateParams(): void
    {
        if (empty($this->params['Channel'])) {
            throw new InvalidArgumentException('Channel is required for Originate');
        }

        $hasExten = !empty($this->params['Exten']);
        $hasApp   = !empty($this->params['Application']);

        if (!$hasExten && !$hasApp) {
            throw new InvalidArgumentException(
                'Must provide either Exten+Context+Priority or Application+Data'
            );
        }

        if ($hasExten && (empty($this->params['Context']) || !isset($this->params['Priority']))) {
            throw new InvalidArgumentException('Context and Priority required when using Exten');
        }
    }
}