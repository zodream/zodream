<?php
declare(strict_types=1);
namespace Zodream\Infrastructure\Queue;


use Zodream\Infrastructure\Pool\EnginePool;
use Zodream\Infrastructure\Queue\Events\JobFailed;
use Zodream\Infrastructure\Queue\Failed\FailedJobProviderInterface;

class QueuePool extends EnginePool {

    protected string $configKey = 'queue';

    /**
     * @var Queue[]
     */
    protected array $engines = [];

    /**
     * @var FailedJobProviderInterface
     */
    protected $failedProvider;

    protected string $defaultDriver = NullQueue::class;

    /**
     * @return FailedJobProviderInterface|null
     */
    public function getFailedProvider() {
        if (!empty($this->failedProvider)) {
            return $this->failedProvider;
        }
        $configs = $this->getConfig();
        if (!isset($configs['failer'])) {
            return null;
        }
        $class = $configs['failer'];
        return $this->failedProvider = new $class($configs['fail_table']);
    }

    /**
     * @param string $name
     * @return Queue
     * @throws \Exception
     */
    public function get(string $name = ''): Queue {
        return $this->getEngine($name);
    }

    public function logFailedJob(): void {
        event()->listen(JobFailed::class, function (JobFailed $event) {
            $failer = $this->getFailedProvider();
            if (empty($failer)) {
                return;
            }
            $failer->log(
                $event->connectionName, $event->job->getQueue(),
                $event->job->getRawBody(), $event->exception
            );
        });
    }
}