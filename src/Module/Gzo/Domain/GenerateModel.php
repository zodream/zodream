<?php
namespace Zodream\Module\Gzo\Domain;
/**
 * Created by PhpStorm.
 * User: zx648
 * Date: 2016/3/17
 * Time: 22:49
 */
/**
SHOW DATABASES                                //列出 MySQL Server 数据库。
SHOW TABLES [FROM db_name]                    //列出数据库数据表。
SHOW CREATE TABLES tbl_name                    //导出数据表结构。
SHOW TABLE STATUS [FROM db_name]              //列出数据表及表状态信息。
SHOW COLUMNS FROM tbl_name [FROM db_name]     //列出资料表字段
SHOW FIELDS FROM tbl_name [FROM db_name]，DESCRIBE tbl_name [col_name]。
SHOW FULL COLUMNS FROM tbl_name [FROM db_name]//列出字段及详情
SHOW FULL FIELDS FROM tbl_name [FROM db_name] //列出字段完整属性
SHOW INDEX FROM tbl_name [FROM db_name]       //列出表索引。
SHOW STATUS                                  //列出 DB Server 状态。
SHOW VARIABLES                               //列出 MySQL 系统环境变量。
SHOW PROCESSLIST                             //列出执行命令。
SHOW GRANTS FOR user                         //列出某用户权限
 */
use Zodream\Domain\Model\Model;
use Zodream\Infrastructure\Database\Schema\Schema;

class GenerateModel extends Model {

    public static function schema($name = null) {
        return new Schema($name);
    }

    public static function getValidate($value) {
        $result = '';
        if ($value['Null'] == 'NO') {
            $result = 'required';
        }
        if ($value['Type'] == 'text') {
            return $result;
        }

        if(!preg_match('#(.+?)\(([0-9]+)\)#', $value['Type'], $match)) {
            return $result;
        }
        switch ($match[1]) {
            case 'int':
                $result .= '|int';
                break;
            case 'tinyint':
                $result .= '|int:0-'.$match[2];
                break;
            case 'char':
            case 'varchar':
            default:
                $result .= '|string:3-'.$match[2];
                break;
        }
        return trim($result, '|');
    }

    /**
     * 数据模型中的列生成
     * @param array $columns
     * @return string
     */
    public static function getFill(array $columns) {
        $pk = $rules = $labels = $property = [];
        foreach ($columns as $key => $value) {
            $labels[$value['Field']] = ucwords(str_replace('_', ' ', $value['Field']));
            $property[$value['Field']] = static::converterType($value['Type']);
            if ($value['Key'] == 'PRI'
                || $value['Key'] == 'UNI') {
                $pk[] = $value['Field'];
            }
            if ($value['Extra'] === 'auto_increment') {
                continue;
            }
            $rules[$value['Field']] = static::getValidate($value);
        }
        return [
            $pk,
            $rules,
            $labels,
            $property
        ];
    }

    protected static function converterType($type) {
        $type = explode('(', $type)[0];
        switch (strtoupper(trim($type))) {
            case 'INT':
            case 'BOOL':
            case 'TINYINT':
            case 'SMALLINT':
            case 'REAL':
            case 'MEDIUMINT':
            case 'BIGINT':
                return 'integer';
            case 'DOUBLE':
                return 'double';
            case 'FLOAT':
            case 'DECIMAL':
                return 'float';
            default:
                return 'string';
        }
    }
}