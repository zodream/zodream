<?php
namespace Zodream\Infrastructure\Session;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/6
 * Time: 9:56
 */
use Zodream\Database\Command;
use Zodream\Helpers\Str;

class DatabaseSession extends Session {

    protected $configs = [
        'table' => 'session'
    ];
    /**
     * @return Command
     */
    protected function command() {
        return Command::getInstance()->setTable('session');
    }

    public function useCustomStorage() {
        return true;
    }

    public function regenerateID($deleteOldSession = false) {
        $oldID = session_id();

        // if no session is started, there is nothing to regenerate
        if (empty($oldID)) {
            return;
        }
        $newID = session_id();
        $command = $this->command();
        $sql = sprintf('SELECT * FROM %s WHERE id = ? LIMIT 1', $command->getTable());
        $data = $command->select($sql, [$newID]);
        if (!empty($data)) {
            if ($deleteOldSession) {
                $sql = sprintf('UPDATE %s SET id = ? WHERE id = ?', $command->getTable());
                $command->update($sql, [$newID, $oldID]);
            } else {
                $row = current($data);
                $row['id'] = $newID;
                $sql = sprintf('INSERT INTO %s(`%s`) VALUES (%s)', $command->getTable(),
                    implode('`,`', array_keys($row)), Str::repeat('?', count($row)));
                $command->insert(
                    $sql,
                    array_values($row));
            }
        } else {
            $sql = sprintf('INSERT INTO %s (`id`) VALUES (?)', $command->getTable());
            $command->insert($sql, [$newID]);
        }
    }

    public function readSession($id) {
        $command = $this->command();
        $sql = sprintf('SELECT `data` FROM %s WHERE id = ? AND expire > ? LIMIT 1', $command->getTable());
        $data = $command->select($sql, [$id, time()]);
        if (empty($data)) {
            return '';
        }
        return current($data)['data'];
    }

    public function writeSession($id, $data) {
        try {
            $command = $this->command();
            $sql = sprintf('SELECT * FROM %s WHERE id = ? LIMIT 1', $command->getTable());
            $exists = $command->select($sql, [$id]);
            if (empty($exists)) {
                $sql = sprintf('INSERT INTO %s (`id`, `data`) VALUES (?, ?)', $command->getTable());
                $command->insert($sql, [$id, $data]);
            } else {
                $sql = sprintf('UPDATE %s SET `data` = ? WHERE id = ?', $command->getTable());
                $command->update($sql, [$data, $id]);
            }
        } catch (\Exception $e) {
            logger('WRITESESSION FAIL:'.$e->getMessage());
        }
        return true;
    }

    public function destroySession($id) {
        $command = $this->command();
        $sql = sprintf('DELETE FROM %s WHERE id = ?', $command->getTable());
        $this->command()->delete($sql, [$id]);
        return true;
    }

    public function gcSession($maxLifetime) {
        $command = $this->command();
        $sql = sprintf('DELETE FROM %s WHERE expire < ?', $command->getTable());
        $this->command()->delete($sql, [time()]);
        return true;
    }
}