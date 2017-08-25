<?php
namespace Zodream\Infrastructure\Session;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/6
 * Time: 9:56
 */
use Zodream\Infrastructure\Database\Command;
use Zodream\Infrastructure\Error\Error;
use Zodream\Infrastructure\ObjectExpand\StringExpand;

class DatabaseCache extends Session {

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
        $data = $this->command()->select('WHERE id = ? LIMIT 1', '*', [$newID]);
        if (!empty($data)) {
            if ($deleteOldSession) {
                $this->command()->update('id = ?', 'id = ?', [$newID, $oldID]);
            } else {
                $row = current($data);
                $row['id'] = $newID;
                $this->command()->insert('`'.implode('`,`', array_keys($row)).'`',
                    StringExpand::repeat('?', count($row)), array_values($row));
            }
        } else {
            $this->command()->insert('id', '?', [$newID]);
        }
    }

    public function readSession($id) {
        $data = $this->command()
            ->select('WHERE id = ? AND expire > '.time().' LIMIT 1', 'data', [$id]);
        if (empty($data)) {
            return '';
        }
        return current($data)['data'];
    }

    public function writeSession($id, $data) {
        try {
            $exists = $this->command()->select('WHERE id = ? LIMIT 1', '*', [$id]);
            if (empty($exists)) {
                $this->command()->insert('id, data', '?, ?', [$id, $data]);
            } else {
                $this->command()->update('data = ?', 'id = ?', [$data, $id]);
            }
        } catch (\Exception $e) {
            Error::out('WRITESESSION FAIL', __FILE__, __LINE__);
        }
        return true;
    }

    public function destroySession($id) {
        $this->command()->delete('id = ?', [$id]);
        return true;
    }

    public function gcSession($maxLifetime) {
        $this->command()->delete('expire < '.time());
        return true;
    }
}