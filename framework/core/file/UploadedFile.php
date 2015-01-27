<?php
namespace rock\file;

use rock\base\Alias;
use rock\di\Container;
use rock\events\EventsInterface;
use rock\events\EventsTrait;
use rock\helpers\FileHelper;
use rock\widgets\ActiveHtml;

/**
 * UploadedFile represents the information for an uploaded file.
 *
 * ```php
 * $model = new UploadModel;
 * if (!empty($_FILES)) {
 *   $model->file = UploadedFile::getInstances($model, 'file');
 *
 *   // Validation
 *   $validate = Validate::attributes(Validate::uploaded()
 *          ->fileSizeMax('5k', true)
 *          ->fileExtensions(['gif', 'jpeg']));
 *   if ($validate->validate($model->file)) {
 *      foreach ($model->file as $file) {
 *          $file->saveAs('/upload/');
 *      }
 *   }
 * }
 *
 * // Render html-form
 * $form = ActiveForm::begin(['options' => ['id' => 'form-upload','enctype'=>'multipart/form-data'], 'model'=> $model]);
 * echo $form->field($model, 'file[]')->fileInput();
 * echo $form->field($model, 'file[]')->fileInput();
 * echo Html::submitButton();
 * ActiveForm::end();
 * ```
 *
 * You can call {@see \rock\file\UploadedFile::getInstance()} to retrieve the instance of an uploaded file,
 * and then use {@see \rock\file\UploadedFile::saveAs()} to save it on the server.
 * You may also query other information about the file, including {@see \rock\file\UploadedFile::$name},
 * {@see \rock\file\UploadedFile::$tempName}, {@see \rock\file\UploadedFile::$type}, {@see \rock\file\UploadedFile::$size} and {@see \rock\file\UploadedFile::$error}.
 *
 * @property string $baseName Original file base name. This property is read-only.
 * @property string $extension File extension. This property is read-only.
 * @property boolean $hasError Whether there is an error with the uploaded file. Check {@see \rock\file\UploadedFile::$error} for detailed
 * error code information. This property is read-only.
 *
 */
class UploadedFile implements EventsInterface
{
    use EventsTrait;

    private static $_files;

    /**
     * @var string the original name of the file being uploaded
     */
    public $name;
    /**
     * @var string the path of the uploaded file on the server.
     * Note, this is a temporary file which will be automatically deleted by PHP
     * after the current request is processed.
     */
    public $tempName;
    /**
     * @var string the MIME-type of the uploaded file (such as "image/gif").
     * Since this MIME type is not checked on the server side, do not take this value for granted.
     */
    public $type;
    /**
     * @var integer the actual size of the uploaded file in bytes
     */
    public $size;
    /**
     * @var integer an error code describing the status of this file uploading.
     * @see http://www.php.net/manual/en/features.file-upload.errors.php
     */
    public $error;
    /** @var FileManager|array */
    public $adapter;
    /** @var  callable */
    public $calculatePathname;

    public function init()
    {
        if (!is_object($this->adapter)) {
            if (class_exists('\rock\di\Container')) {
                $this->adapter = Container::load($this->adapter);
                return;
            }
            throw new FileException(FileException::NOT_OBJECT, ['name' => FileManager::className()]);
        }
    }

    /**
     * String output.
     * This is PHP magic method that returns string representation of an object.
     * The implementation here returns the uploaded file's name.
     * @return string the string representation of the object
     */
    public function __toString()
    {
        return $this->name;
    }

    /**
     * Returns an uploaded file for the given model attribute.
     * The file should be uploaded using {@see \rock\widgets\ActiveField::fileInput()}.
     *
     * @param \rock\components\Model $model     the data model
     * @param string                 $attribute the attribute name. The attribute name may contain array indexes.
     *                                          For example, '[1]file' for tabular file uploading; and 'file[1]' for an element in a file array.
     * @return UploadedFile the instance of the uploaded file.
     *                                          Null is returned if no file is uploaded for the specified model attribute.
     * @throws FileException
     * @throws \rock\template\HtmlException
     * @see getInstanceByName()
     */
    public static function getInstance($model, $attribute)
    {
        if (!class_exists('\rock\widgets\ActiveHtml')) {
            throw new FileException(FileException::NOT_INSTALL_TEMPLATE);
        }
        $name = ActiveHtml::getInputName($model, $attribute);
        return static::getInstanceByName($name);
    }

    /**
     * Returns all uploaded files for the given model attribute.
     *
     * @param \rock\components\Model $model     the data model
     * @param string                 $attribute the attribute name. The attribute name may contain array indexes
     *                                          for tabular file uploading, e.g. '[1]file'.
     * @return UploadedFile[] array of UploadedFile objects.
     *                                          Empty array is returned if no available file was found for the given attribute.
     * @throws FileException
     * @throws \rock\template\HtmlException
     */
    public static function getInstances($model, $attribute)
    {
        if (!class_exists('\rock\widgets\ActiveHtml')) {
            throw new FileException(FileException::NOT_INSTALL_TEMPLATE);
        }
        $name = ActiveHtml::getInputName($model, $attribute);
        return static::getInstancesByName($name);
    }

    /**
     * Returns an uploaded file according to the given file input name.
     * The name can be a plain string or a string like an array element (e.g. 'Post[imageFile]', or 'Post[0][imageFile]').
     * @param string $name the name of the file input field.
     * @return UploadedFile the instance of the uploaded file.
     * Null is returned if no file is uploaded for the specified name.
     */
    public static function getInstanceByName($name)
    {
        $files = self::loadFiles();
        return isset($files[$name]) ? $files[$name] : null;
    }

    /**
     * Returns an array of uploaded files corresponding to the specified file input name.
     * This is mainly used when multiple files were uploaded and saved as 'files[0]', 'files[1]',
     * 'files[n]'..., and you can retrieve them all by passing 'files' as the name.
     * @param string $name the name of the array of files
     * @return UploadedFile[] the array of CUploadedFile objects. Empty array is returned
     * if no adequate upload was found. Please note that this array will contain
     * all files from all sub-arrays regardless how deeply nested they are.
     */
    public static function getInstancesByName($name)
    {
        $files = self::loadFiles();
        if (isset($files[$name])) {
            return [$files[$name]];
        }
        $results = [];
        foreach ($files as $key => $file) {
            if (strpos($key, "{$name}[") === 0) {
                $results[] = $file;
            }
        }
        return $results;
    }

    /**
     * Returns the maximum size allowed for uploaded files.
     * This is determined based on three factors:
     *
     * - 'upload_max_filesize' in php.ini
     * - 'MAX_FILE_SIZE' hidden field
     * - `maxSize`
     *
     * @param int|null $maxSize
     * @return integer the size limit for uploaded files.
     */
    public static  function getSizeLimit($maxSize = null)
    {
        $limit = FileHelper::sizeToBytes(ini_get('upload_max_filesize'));
        if ($maxSize !== null) {
            $maxSize = FileHelper::sizeToBytes($maxSize);
            if ($limit > 0 && $maxSize < $limit) {
                $limit = $maxSize;
            }
        }

        if (isset($_POST['MAX_FILE_SIZE']) && $_POST['MAX_FILE_SIZE'] > 0 && $_POST['MAX_FILE_SIZE'] < $limit) {
            $limit = (int) $_POST['MAX_FILE_SIZE'];
        }

        return $limit;
    }

    /**
     * Cleans up the loaded UploadedFile instances.
     * This method is mainly used by test scripts to set up a fixture.
     */
    public function reset()
    {
        self::$_files = null;
    }

    /**
     * Saves the uploaded file.
     * Note that this method uses php's move_uploaded_file() method. If the target file `$file`
     * already exists, it will be overwritten.
     *
     * @param string  $file           the file path used to save the uploaded file
     * @param boolean $deleteTempFile whether to delete the temporary file after saving.
     *                                If true, you will not be able to save the uploaded file again in the current request.
     * @param bool    $createDir
     * @return bool true whether the file is saved successfully
     * @throws \Exception
     * @see error
     */
    public function saveAs($file, $deleteTempFile = true, $createDir = false)
    {
        if ($this->error !== UPLOAD_ERR_OK) {
            return false;
        }
        $file = Alias::getAlias($file);
        if ($this->calculatePathname instanceof \Closure) {
            $file = call_user_func($this->calculatePathname, $this, $file, $this->adapter);
        }
        if ($createDir) {
            FileHelper::createDirectory(dirname($file));
        }
        if ($deleteTempFile) {
            return move_uploaded_file($this->tempName, $file);
        } elseif (is_uploaded_file($this->tempName)) {
            return copy($this->tempName, $file);
        }

        return false;
    }

    /**
     * @return string original file base name
     */
    public function getBaseName()
    {
        return pathinfo($this->name, PATHINFO_FILENAME);
    }

    /**
     * @return string file extension
     */
    public function getExtension()
    {
        return strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
    }

    /**
     * @return boolean whether there is an error with the uploaded file.
     * Check {@see \rock\file\UploadedFile::$error} for detailed error code information.
     */
    public function getHasError()
    {
        return $this->error != UPLOAD_ERR_OK;
    }

    /**
     * Creates UploadedFile instances from $_FILE.
     * @return array the UploadedFile instances
     */
    private static function loadFiles()
    {
        if (self::$_files === null) {
            self::$_files = [];
            if (isset($_FILES) && is_array($_FILES)) {
                foreach ($_FILES as $class => $info) {
                    self::loadFilesRecursive($class, $info['name'], $info['tmp_name'], $info['type'], $info['size'], $info['error']);
                }
            }
        }
        return self::$_files;
    }

    /**
     * Creates UploadedFile instances from $_FILE recursively.
     * @param string $key key for identifying uploaded file: class name and sub-array indexes
     * @param mixed $names file names provided by PHP
     * @param mixed $tempNames temporary file names provided by PHP
     * @param mixed $types file types provided by PHP
     * @param mixed $sizes file sizes provided by PHP
     * @param mixed $errors uploading issues provided by PHP
     */
    private static function loadFilesRecursive($key, $names, $tempNames, $types, $sizes, $errors)
    {
        if (is_array($names)) {
            foreach ($names as $i => $name) {
                self::loadFilesRecursive($key . '[' . $i . ']', $name, $tempNames[$i], $types[$i], $sizes[$i], $errors[$i]);
            }
        } else {
            $config = [
                'class' => static::className(),
                'name' => $names,
                'tempName' => $tempNames,
                'type' => $types,
                'size' => FileHelper::fixedIntegerOverflow($sizes),
                'error' => $errors,
            ];
            self::$_files[$key] = static::getSelfInstance($config);
        }
    }

    /**
     * Get instance.
     *
     * If exists {@see \rock\di\Container} that uses it.
     *
     * @param string|array $config the configuration. It can be either a string representing the class name
     *                                     or an array representing the object configuration.
     * @return $this
     * @throws \rock\di\ContainerException
     */
    protected static function getSelfInstance($config)
    {
        if (class_exists('\rock\di\Container')) {
            return Container::load($config);
        }
        unset($config['class']);
        return new static($config);
    }
}