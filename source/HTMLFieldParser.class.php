<?php
/**
 * Developer: Page Carbajal (https://github.com/Page-Carbajal)
 * Date: October 21 2015, 6:30 PM
 */

namespace WPExpress\UI;


class HTMLFieldParser
{

    // This class is inspired by Groovy's FormTagLib.groovy
    // https://github.com/grails/grails-core/blob/0b1d6a6d02f2217643a69e8314f76078dacbce32/grails-plugin-gsp/src/main/groovy/org/grails/plugins/web/taglib/FormTagLib.groovy

    private $fieldCollection;

    public function __construct( $fields )
    {
        if( !empty( $fields ) && ( $fields instanceof FieldCollection ) ) {
            $this->fields = $fields;
            $this->parseFields();
        }
    }

    private static function getBaseAttributes( $name, $ID )
    {
        $controlID = empty( $ID ) ? $name : $ID;
        return array( 'id' => $controlID, 'name' => $name );
    }

    public static function textField( $name, $attributes, $ID = null )
    {
        $attributes         = self::getBaseAttributes($name, $ID);
        $attributes['type'] = 'text';

        return self::renderInputField(array_merge($attributes, $attributes));
    }

    public static function hiddenField( $name, $attributes, $ID = null )
    {
        $attributes         = self::getBaseAttributes($name, $ID = null);
        $attributes['type'] = 'hidden';

        return self::renderInputField(array_merge($attributes, $attributes));
    }

    public static function checkboxField( $name, $attributes, $ID = null )
    {
        $attributes         = self::getBaseAttributes($name, $ID);
        $attributes['type'] = 'checkbox';

        return self::renderInputField(array_merge($attributes, $attributes));
    }

    public static function radioButtonField( $name, $attributes, $ID = null )
    {
        $attributes         = self::getBaseAttributes($name, $ID);
        $attributes['type'] = 'radio';

        return self::renderInputField(array_merge($attributes, $attributes));
    }

    protected static function renderInputField( $attributes )
    {
        $atts = self::arrayToHTMLAttributes($attributes);

        $field             = new \stdClass();
        $field->html       = "<input {$atts} />";
        $field->properties = $attributes;

        return $field;
    }

    protected static function arrayToHTMLAttributes( $list, $includeValue = true )
    {
        $attributes = array_map(function ( $key ) use ( $list, $includeValue ) {
            if( is_bool($list[$key]) ) {
                return ( $list[$key] ? $key : '' );
            }
            if( $key == 'value' && ( !$includeValue ) ) {
                return ""; //if value should not be rendered as an attribute
            }
            $propertyValue = esc_html($list[$key]);
            return "{$key}=\"{$propertyValue}\"";
        }, array_keys($list));
        return implode(' ', $attributes);
    }

    /**
     * Generate a Select Field.
     * @param $name
     * @param $options : An array with items. Accepts simple array or nested array with the properties [text, value, selected]
     * @param $attributes
     * @return bool|string
     */
    public static function selectField( $name, $options, $attributes )
    {
        if( empty( $name ) ) {
            return false;
        }

        if( empty( $options ) ) {
            trigger_error("Object <strong>{$name}</strong> has no valid options!", E_USER_WARNING);
            return false;
        }

        $properties = array( 'id' => $name, 'name' => $name );
        $source     = '';

        foreach( $options as $item ) {
            $option = '';
            if( !is_array($item) ) {
                $option .= "<option value=\"{$item}\">{$item}</option>";
            } else {
                $option = "<option ";
                if( isset( $item['value'] ) ) {
                    $option .= " value=\"{$item['value']}\"";
                } else {
                    $option .= " value=\"{$item['text']}\"";
                }

                if( isset( $item['selected'] ) && $item['selected'] == true ) {
                    $option .= ' selected="selected" ';
                }
                $option = trim($option) . ">{$item['text']}</option>";
            }
            $source .= $option;
        }

        $attsString = self::arrayToHTMLAttributes(array_merge($properties, $attributes));
        $output     = "<select {$attsString}>{$source}</select>";

        $field             = new \stdClass();
        $field->html       = $output;
        $field->properties = array_merge($properties, $attributes, array( 'options' => $options ));

        return $field;
    }

    public static function textArea( $name, $value, $attributes, $ID = null )
    {
        $properties = self::getBaseAttributes($name, $ID);
        $attsString = self::arrayToHTMLAttributes(array_merge($properties, $attributes));
        $output     = "<textarea {$attsString}>{$value}</textarea>";

        return $output;
    }


    public function parseFields()
    {
        $list = array();

        foreach( $this->fields as $field ) {

            switch( $field->type ) {
                case "select":
                    // Why not simply pass the field object? Well, we could, but we'll be forcing you to use our FieldCollection. So we keep it cool.
                    $list[] = $this->selectField($field->name, $field->properties['options'], $field->attributes);
                    break;
                case "radio":
                    $list[] = $this->radioButtonField($field->name, $field->attributes);
                    break;
                case "radiobutton":
                    $list[] = $this->radioButtonField($field->name, $field->attributes);
                    break;
                case "check":
                    $list[] = $this->checkboxField($field->name, $field->attributes);
                    break;
                case "checkbox":
                    $list[] = $this->checkboxField($field->name, $field->attributes);
                    break;
                case "textarea":
                    $list[] = $this->textArea($field->name, $field->properties['value'], $field->attributes);
                    break;
                default:
                    $list[] = $this->textField($field->name, $field->attributes);
                    break;
            }

        }

        return $list;
    }

}