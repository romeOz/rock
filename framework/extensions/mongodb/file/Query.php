<?php

namespace rock\mongodb\file;

use rock\di\Container;

/**
 * Query represents Mongo "find" operation for GridFS collection.
 *
 * Query behaves exactly as regular {@see \rock\mongodb\Query}.
 * Found files will be represented as arrays of file document attributes with
 * additional 'file' key, which stores {@see \MongoGridFSFile} instance.
 *
 * @property Collection $collection Collection instance. This property is read-only.
 */
class Query extends \rock\mongodb\Query
{
    /**
     * Returns the Mongo collection for this query.
     *
     * @param \rock\mongodb\Connection $connection Mongo connection.
     * @return Collection collection instance.
     */
    public function getCollection($connection = null)
    {
        if ($connection === null) {
            $this->connection = is_string($this->connection) ? Container::load($this->connection) : $this->connection;
        } else {
            $this->connection = $connection;
        }
        $this->calculateCacheParams($this->connection);
        return $this->connection->getFileCollection($this->from);
    }

    /**
     * @param \MongoGridFSCursor $cursor Mongo cursor instance to fetch data from.
     * @param boolean $all whether to fetch all rows or only first one.
     * @param string|callable $indexBy value to index by.
     * @return array|boolean result.
     * @see Query::fetchRows()
     */
    protected function fetchRowsInternal($cursor, $all, $indexBy)
    {
        $result = [];
        if ($all) {
            foreach ($cursor as $file) {
                $row = $file->file;
                $row['file'] = $file;
                if ($indexBy !== null) {
                    if (is_string($indexBy)) {
                        $key = $row[$indexBy];
                    } else {
                        $key = call_user_func($indexBy, $row);
                    }
                    $result[$key] = $row;
                } else {
                    $result[] = $row;
                }
            }
        } else {
            if ($cursor->hasNext()) {
                $file = $cursor->getNext();
                $result = $file->file;
                $result['file'] = $file;
            } else {
                $result = null;
            }
        }

        return $result;
    }
}