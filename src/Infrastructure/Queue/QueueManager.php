<?php
namespace Zodream\Infrastructure\Queue;


use Zodream\Database\Manager;
use Zodream\Infrastructure\Queue\Events\JobFailed;
use Zodream\Infrastructure\Queue\Failed\FailedJobProviderInterface;
use Zodream\Infrastructure\Concerns\SingletonPattern;

class QueueManager extends Manager {
    use SingletonPattern;

    protected $configKey = 'queue';

    /**
     * @var Queue[]
     */
    protected $engines = [];

    /**
     * @var FailedJobProviderInterface
     */
    protected $failedProvider;

    protected $defaultDriver = NullQueue::class;

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
    public static function connection($name = null) {
        return static::getInstance()->getEngine($name);
    }

    public static function logFailedJob() {
        event()->listen(JobFailed::class, function (JobFailed $event) {
            $failer = static::getInstance()->getFailedProvider();
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