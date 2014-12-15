<?php
namespace rock\db;

use rock\base\ObjectTrait;

/**
 * ColumnSchema class describes the metadata of a column in a database table.
 */
class ColumnSchema
{
    use ObjectTrait;

    /**
     * @var string name of this column (without quotes).
     */
    public $name;
    /**
     * @var boolean whether this column can be null.
     */
    public $allowNull;
    /**
     * @var string abstract type of this column. Possible abstract types include:
     * string, text, boolean, smallint, integer, bigint, float, decimal, datetime,
     * timestamp, time, date, binary, and money.
     */
    public $type;
    /**
     * @var string the PHP type of this column. Possible PHP types include:
     * `string`, `boolean`, `integer`, `double`.
     */
    public $phpType;
    /**
     * @var string the DB type of this column. Possible DB types vary according to the type of DBMS.
     */
    public $dbType;
    /**
     * @var mixed default value of this column
     */
    public $defaultValue;
    /**
     * @var array enumerable values. This is set only if the column is declared to be an enumerable type.
     */
    public $enumValues;
    /**
     * @var integer display size of the column.
     */
    public $size;
    /**
     * @var integer precision of the column data, if it is numeric.
     */
    public $precision;
    /**
     * @var integer scale of the column data, if it is numeric.
     */
    public $scale;
    /**
     * @var boolean whether this column is a primary key
     */
    public $isPrimaryKey;
    /**
     * @var boolean whether this column is auto-incremental
     */
    public $autoIncrement = false;
    /**
     * @var boolean whether this column is unsigned. This is only meaningful
     * when {@see \rock\db\ColumnSchema::$type} is `smallint`, `integer` or `bigint`.
     */
    public $unsigned;
    /**
     * @var string comment of this column. Not all DBMS support this.
     */
    public $comment;


    /**
     * Converts the input value according to {@see \rock\db\ColumnSchema::$phpType} after retrieval from the database.
     * If the value is null or an {@see \rock\db\Expression}, it will not be converted.
     * @param mixed $value input value
     * @return mixed converted value
     */
    public function phpTypecast($value)
    {
        if ($value === '' && $this->type !== Schema::TYPE_TEXT && $this->type !== Schema::TYPE_STRING && $this->type !== Schema::TYPE_BINARY) {
            return null;
        }
        if ($value === null || gettype($value) === $this->phpType || $value instanceof Expression) {
            return $value;
        }
        switch ($this->phpType) {
            case 'resource':
            case 'string':
                return is_resource($value) ? $value : (string) $value;
            case 'integer':
                return (int) $value;
            case 'boolean':
                return (bool) $value;
            case 'double':
                return (double) $value;
        }

        return $value;
    }

    /**
     * Converts the input value according to {@see \rock\db\ColumnSchema::$type} and {@see \rock\db\ColumnSchema::$dbType} for use in a db query.
     * If the value is null or an {@see \rock\db\Expression}, it will not be converted.
     * @param mixed $value input value
     * @return mixed converted value. This may also be an array containing the value as the first element
     * and the PDO type as the second element.
     */
    public function dbTypecast($value)
    {
        // the default implementation does the same as casting for PHP but it should be possible
        // to override this with annotation of explicit PDO type.
        return $this->phpTypecast($value);
    }
}
