<?php
namespace rock\db\mssql;

/**
 * TableSchema represents the metadata of a database table.
 */
class TableSchema extends \rock\db\TableSchema
{
    /**
     * @var string name of the catalog (database) that this table belongs to.
     * Defaults to null, meaning no catalog (or the current database).
     */
    public $catalogName;
}
