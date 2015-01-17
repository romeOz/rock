<?php
namespace rock\mongodb\file;

use rock\di\Container;
use rock\mongodb\MongoException;
use rock\Rock;

/**
 * Collection represents the Mongo GridFS collection information.
 *
 * A file collection object is usually created by calling {@see \rock\mongodb\Database::getFileCollection()} or {@see \rock\mongodb\Connection::getFileCollection()}.
 *
 * File collection inherits all interface from regular {@see \rock\mongodb\Collection}, adding methods to store files.
 *
 * @property \rock\mongodb\Collection $chunkCollection Mongo collection instance. This property is read-only.
 *
 */
class Collection extends \rock\mongodb\Collection
{
    /**
     * @var \MongoGridFS Mongo GridFS collection instance.
     */
    public $mongoCollection;

    /**
     * @var \rock\mongodb\Collection file chunks Mongo collection.
     */
    private $_chunkCollection;


    /**
     * Returns the Mongo collection for the file chunks.
     * @param boolean $refresh whether to reload the collection instance even if it is found in the cache.
     * @return \rock\mongodb\Collection mongo collection instance.
     */
    public function getChunkCollection($refresh = false)
    {
        if ($refresh || !is_object($this->_chunkCollection)) {
            $this->_chunkCollection = Container::load([
                'class' => \rock\mongodb\Collection::className(),
                'mongoCollection' => $this->mongoCollection->chunks,
            ]);
        }

        return $this->_chunkCollection;
    }

    /**
     * Removes data from the collection.
     *
*@param array $condition description of records to remove.
     * @param array $options list of options in format: optionName => optionValue.
     * @return integer|boolean number of updated documents or whether operation was successful.
     * @throws MongoException on failure.
     */
    public function remove($condition = [], $options = [])
    {
        $result = parent::remove($condition, $options);
        $this->tryLastError(); // MongoGridFS::remove will return even if the remove failed

        return $result;
    }

    /**
     * Creates new file in GridFS collection from given local filesystem file.
     * Additional attributes can be added file document using $metadata.
     *
*@param string $filename name of the file to store.
     * @param array $metadata other metadata fields to include in the file document.
     * @param array $options list of options in format: optionName => optionValue
     * @return mixed the "_id" of the saved file document. This will be a generated {@see \MongoId}
     * unless an "_id" was explicitly specified in the metadata.
     * @throws MongoException on failure.
     */
    public function insertFile($filename, $metadata = [], $options = [])
    {
        $token = 'Inserting file into ' . $this->getFullName();
        //Rock::info($token);
        Rock::beginProfile('mongodb.query', $token);
        try {
            $options = array_merge(['w' => 1], $options);
            $result = $this->mongoCollection->storeFile($filename, $metadata, $options);
            Rock::endProfile('mongodb.query', $token);
            Rock::trace('mongodb.query', $token);

            return $result;
        } catch (\Exception $e) {
            $message = $e->getMessage() . "\nThe query being executed was: $token";
            Rock::endProfile('mongodb.query', $token);
            $token['valid']     = false;
            $token['exception'] = DEBUG === true ? $e : $message;
            Rock::trace('mongodb.query', $token);

            throw new MongoException($message, [], $e);
        }
    }

    /**
     * Creates new file in GridFS collection with specified content.
     * Additional attributes can be added file document using $metadata.
     *
*@param string $bytes string of bytes to store.
     * @param array $metadata other metadata fields to include in the file document.
     * @param array $options list of options in format: optionName => optionValue
     * @return mixed the "_id" of the saved file document. This will be a generated {@see \MongoId}
     * unless an "_id" was explicitly specified in the metadata.
     * @throws MongoException on failure.
     */
    public function insertFileContent($bytes, $metadata = [], $options = [])
    {
        $token = 'Inserting file content into ' . $this->getFullName();
        //Rock::info($token);
        Rock::beginProfile('mongodb.query', $token);
        try {
            $options = array_merge(['w' => 1], $options);
            $result = $this->mongoCollection->storeBytes($bytes, $metadata, $options);
            Rock::endProfile('mongodb.query', $token);
            Rock::trace('mongodb.query', $token);

            return $result;
        } catch (\Exception $e) {
            $message = $e->getMessage() . "\nThe query being executed was: $token";
            Rock::endProfile('mongodb.query', $token);
            $token['valid']     = false;
            $token['exception'] = DEBUG === true ? $e : $message;
            Rock::trace('mongodb.query', $token);

            throw new MongoException($message, [], $e);
        }
    }

    /**
     * Creates new file in GridFS collection from uploaded file.
     * Additional attributes can be added file document using $metadata.
     *
*@param string $name name of the uploaded file to store. This should correspond to
     * the file field's name attribute in the HTML form.
     * @param array $metadata other metadata fields to include in the file document.
     * @return mixed the "_id" of the saved file document. This will be a generated {@see \MongoId}
     * unless an "_id" was explicitly specified in the metadata.
     * @throws MongoException on failure.
     */
    public function insertUploads($name, $metadata = [])
    {
        $token = 'Inserting file uploads into ' . $this->getFullName();
        //Rock::info($token);
        Rock::beginProfile('mongodb.query', $token);
        try {
            $result = $this->mongoCollection->storeUpload($name, $metadata);
            Rock::endProfile('mongodb.query', $token);
            Rock::trace('mongodb.query', $token);

            return $result;
        } catch (\Exception $e) {
            $message = $e->getMessage() . "\nThe query being executed was: $token";
            Rock::endProfile('mongodb.query', $token);
            $token['valid']     = false;
            $token['exception'] = DEBUG === true ? $e : $message;
            Rock::trace('mongodb.query', $token);

            throw new MongoException($message, [], $e);
        }
    }

    /**
     * Retrieves the file with given _id.
     *
*@param mixed $id _id of the file to find.
     * @return \MongoGridFSFile|null found file, or null if file does not exist
     * @throws MongoException on failure.
     */
    public function get($id)
    {
        $token = 'Inserting file uploads into ' . $this->getFullName();
        //Rock::info($token);
        Rock::beginProfile('mongodb.query', $token);
        try {
            $result = $this->mongoCollection->get($id);
            Rock::endProfile('mongodb.query', $token);
            Rock::trace('mongodb.query', $token);

            return $result;
        } catch (\Exception $e) {
            $message = $e->getMessage() . "\nThe query being executed was: $token";
            Rock::endProfile('mongodb.query', $token);
            $token['valid']     = false;
            $token['exception'] = DEBUG === true ? $e : $message;
            Rock::trace('mongodb.query', $token);

            throw new MongoException($message, [], $e);
        }
    }

    /**
     * Deletes the file with given _id.
     *
*@param mixed $id _id of the file to find.
     * @return boolean whether the operation was successful.
     * @throws MongoException on failure.
     */
    public function delete($id)
    {
        $token = 'Inserting file uploads into ' . $this->getFullName();
        //Rock::info($token);
        Rock::beginProfile('mongodb.query', $token);
        try {
            $result = $this->mongoCollection->delete($id);
            $this->tryResultError($result);
            Rock::endProfile('mongodb.query', $token);
            Rock::trace('mongodb.query', $token);

            return true;
        } catch (\Exception $e) {
            $message = $e->getMessage() . "\nThe query being executed was: $token";
            Rock::endProfile('mongodb.query', $token);
            $token['valid']     = false;
            $token['exception'] = DEBUG === true ? $e : $message;
            Rock::trace('mongodb.query', $token);
            throw new MongoException($message, [], $e);
        }
    }
}
