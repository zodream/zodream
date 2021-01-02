<?php
namespace Zodream\Infrastructure\Session;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/6
 * Time: 9:56
 */
use Zodream\Helpers\Str;
use Zodream\Infrastructure\Contracts\Database;

class DatabaseSession extends Session {

    protected $configs = [
        'table' => 'session'
    ];

    /**
     * @var Database
     */
    protected $db;

    public function __construct(Database $db) {
        parent::__construct();
        $this->db = $db;
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
        $sql = sprintf('SELECT * FROM %s WHERE id = ? LIMIT 1', $this->tableName());
        $data = $this->db->first($sql, [$newID]);
        if (!empty($data)) {
            if ($deleteOldSession) {
                $sql = sprintf('UPDATE %s SET id = ? WHERE id = ?', $this->tableName());
                $this->db->update($sql, [$newID, $oldID]);
            } else {
                $row = current($data);
                $row['id'] = $newID;
                $sql = sprintf('INSERT INTO %s(`%s`) VALUES (%s)', $this->tableName(),
                    implode('`,`', array_keys($row)), Str::repeat('?', count($row)));
                $this->db->insert(
                    $sql,
                    array_values($row));
            }
        } else {
            $sql = sprintf('INSERT INTO %s (`id`) VALUES (?)', $this->tableName());
            $this->db->insert($sql, [$newID]);
        }
    }

    public function readSession($id) {
        $sql = sprintf('SELECT `data` FROM %s WHERE id = ? AND expire > ? LIMIT 1', $this->tableName());
        $data = $this->db->executeScalar($sql, [$id, time()]);
        if (empty($data)) {
            return '';
        }
        return $data;
    }

    public function writeSession($id, $data) {
        try {
            $sql = sprintf('SELECT * FROM %s WHERE id = ? LIMIT 1', $this->tableName());
            $exists = $this->db->first($sql, [$id]);
            if (empty($exists)) {
                $sql = sprintf('INSERT INTO %s (`id`, `data`) VALUES (?, ?)', $this->tableName());
                $this->db->insert($sql, [$id, $data]);
            } else {
                $sql = sprintf('UPDATE %s SET `data` = ? WHERE id = ?', $this->tableName());
                $this->db->update($sql, [$data, $id]);
            }
        } catch (\Exception $e) {
            logger('WRITESESSION FAIL:'.$e->getMessage());
        }
        return true;
    }

    public function destroySession($id) {
        $sql = sprintf('DELETE FROM %s WHERE id = ?', $this->tableName());
        $this->db->delete($sql, [$id]);
        return true;
    }

    public function gcSession($maxLifetime) {
        $sql = sprintf('DELETE FROM %s WHERE expire < ?', $this->tableName());
        $this->db->delete($sql, [time()]);
        return true;
    }

    protected function tableName() {
        return $this->db->addPrefix($this->configs['table']);
    }
}