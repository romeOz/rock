<?php

namespace rock\widgets;


use rock\components\ComponentsInterface;
use rock\components\ComponentsTrait;
use rock\di\Container;

class Widget implements ComponentsInterface
{
    use ComponentsTrait;

    /**
     * @var integer a counter used to generate `id` for widgets.
     * @internal
     */
    public static $counter = 0;
    /**
     * @var string the prefix to the automatically generated widget IDs.
     * @see getId()
     */
    public static $autoIdPrefix = 'w';

    /**
     * @var Widget[] the widgets that are currently being rendered (not ended). This property
     * is maintained by {@see \rock\widgets\Widget::begin()} and {@see \rock\widgets\Widget::end()} methods.
     * @internal
     */
    public static $stack = [];

    /**
     * Begins a widget.
     * This method creates an instance of the calling class. It will apply the configuration
     * to the created instance. A matching {@see \rock\widgets\Widget::end()} call should be called later.
     * @param array $config name-value pairs that will be used to initialize the object properties
     * @return static the newly created widget instance
     */
    public static function begin($config = [])
    {
        $config['class'] = get_called_class();
        /** @var Widget $widget */
        $widget = Container::load($config);

        self::$stack[] = $widget;

        return $widget;
    }

    /**
     * Ends a widget.
     * Note that the rendering result of the widget is directly echoed out.
     *
     * @return static the widget instance that is ended.
     * @throws WidgetException if {@see \rock\widgets\Widget::begin()} and {@see \rock\widgets\Widget::end()} calls are not properly nested
     */
    public static function end()
    {
        if (!empty(self::$stack)) {
            $widget = array_pop(self::$stack);
            if (get_class($widget) === get_called_class()) {
                $widget->run();

                return $widget;
            } else {
                throw new WidgetException("Expecting end() of " . get_class($widget) . ", found " . get_called_class());
            }
        } else {
            throw new WidgetException("Unexpected " . get_called_class() . '::end() call. A matching begin() is not found.');
        }
    }

    /**
     * Creates a widget instance and runs it.
     * The widget rendering result is returned by this method.
     * @param array $config name-value pairs that will be used to initialize the object properties
     * @return string the rendering result of the widget.
     */
    public static function widget($config = [])
    {
        ob_start();
        ob_implicit_flush(false);
        /** @var Widget $widget */
        $config['class'] = get_called_class();
        $widget = Container::load($config);
        $out = $widget->run();

        return ob_get_clean() . $out;
    }

    private $_id;

    /**
     * Returns the ID of the widget.
     * @param boolean $autoGenerate whether to generate an ID if it is not set previously
     * @return string ID of the widget.
     */
    public function getId($autoGenerate = true)
    {
        if ($autoGenerate && $this->_id === null) {
            $this->_id = self::$autoIdPrefix . self::$counter++;
        }

        return $this->_id;
    }

    /**
     * Sets the ID of the widget.
     * @param string $value id of the widget.
     */
    public function setId($value)
    {
        $this->_id = $value;
    }

    /**
     * Executes the widget.
     * @return string the result of widget execution to be outputted.
     */
    public function run()
    {
    }
} 