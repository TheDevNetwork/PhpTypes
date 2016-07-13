<?php

declare (strict_types = 1);

namespace Tdn\PhpTypes\Type;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Inflector\Inflector;
use Stringy\Stringy;
use Tdn\PhpTypes\Type\Traits\Boxable;
use Tdn\PhpTypes\Type\Traits\Transmutable;
use Tdn\PhpTypes\Exception\InvalidTransformationException;

/**
 * Class StringType.
 *
 * Extends Stringy (https://github.com/danielstjules/Stringy)
 */
class StringType extends Stringy implements TransmutableTypeInterface, ValueInterface
{
    use Transmutable;
    use Boxable;

    /**
     * StringType constructor.
     * Explicitly removing parent constructor. Type conversion should be done through StringType::from($mixed).
     *
     * @param string      $str
     * @param string|null $encoding
     */
    public function __construct(string $str, string $encoding = null)
    {
        $this->str = $str;
        $this->encoding = $encoding ?: \mb_internal_encoding();
    }

    /**
     * Returns the primitive value of current instance casted to specified type.
     *
     * @param int $toType Default: Type::STRING. Options: Type::BOOL, Type::INT, Type::FLOAT, Type::STRING
     *
     * @throws InvalidTransformationException when casted to an unsupported type or casting to number and not numeric.
     *
     * @return mixed
     */
    public function __invoke(int $toType = Type::STRING)
    {
        if ($toType === Type::BOOL) {
            return BooleanType::valueOf($this)->get();
        }

        if ($toType === Type::INT && is_numeric((string) $this)) {
            return IntType::valueOf($this)->get();
        }

        if ($toType === Type::FLOAT && is_numeric((string) $this)) {
            return FloatType::valueOf($this)->get();
        }

        if ($toType !== Type::STRING) {
            throw new InvalidTransformationException(static::class, $this->getTranslatedType($toType));
        }

        return $this->str;
    }

    /**
     * @return string
     */
    public function get()
    {
        return (string) $this;
    }

    /**
     * Creates a new static instance from string.
     *
     * Mainly here for code completion purposes...
     *
     * @param string $str
     * @param string $encoding
     *
     * @return StringType
     */
    public static function create($str = '', $encoding = 'UTF-8') : StringType
    {
        return new static($str, $encoding);
    }

    /**
     * Pluralizes the string.
     *
     * @return StringType
     */
    public function pluralize() : StringType
    {
        return static::create($this->getInflector()->pluralize((string) $this->str), $this->encoding);
    }

    /**
     * Singularizes the string.
     *
     * @return StringType
     */
    public function singularize() : StringType
    {
        return static::create($this->getInflector()->singularize((string) $this->str), $this->encoding);
    }

    /**
     * Returns position of the first occurrence of subStr null if not present.
     *
     * @param string $subStr        Substring
     * @param int    $offset        Chars to offset from start
     * @param bool   $caseSensitive Enable case sensitivity
     *
     * @return IntType
     */
    public function strpos(string $subStr, int $offset = 0, bool $caseSensitive = false) : IntType
    {
        $res = ($caseSensitive) ?
            mb_strpos($this->str, $subStr, $offset, $this->encoding) :
            mb_stripos($this->str, $subStr, $offset, $this->encoding);

        return new IntType($res);
    }

    /**
     * Returns position of the last occurrence of subStr null if not present.
     *
     * @param string $subStr        Substring
     * @param int    $offset        Chars to offset from start
     * @param bool   $caseSensitive Enable case sensitivity
     *
     * @return IntType
     */
    public function strrpos(string $subStr, int $offset = 0, bool $caseSensitive = false) : IntType
    {
        $res = ($caseSensitive) ?
            mb_strrpos($this->str, $subStr, $offset, $this->encoding) :
            mb_strripos($this->str, $subStr, $offset, $this->encoding);

        return new IntType($res);
    }

    /**
     * @return string The current value of the $str property
     */
    public function __toString() : string
    {
        return (string) $this->str;
    }

    /**
     * Override trait to remove spaces.
     *
     * @return BooleanType
     */
    public function toBool() : BooleanType
    {
        return BooleanType::valueOf($this->regexReplace('[[:space:]]', '')->str);
    }

    /**
     * @throws \RuntimeException
     */
    public function toBoolean()
    {
        throw new \RuntimeException('Method has been deprecated. Use "toBool" instead.');
    }

    /**
     * @return DateTimeType
     */
    public function toDateTimeType() : DateTimeType
    {
        return DateTimeType::valueOf($this);
    }

    /**
     * @param mixed $mixed
     *
     * @return StringType
     */
    public static function valueOf($mixed) : StringType
    {
        return new static(self::asString($mixed));
    }

    /**
     * @return Inflector
     */
    private function getInflector() : Inflector
    {
        return new Inflector();
    }

    /**
     * Returns a mixed variable as a string.
     *
     * @param mixed $mixed
     *
     * @return string
     */
    private static function asString($mixed) : string
    {
        if ($mixed instanceof self || $mixed instanceof NumberTypeInterface) {
            return (string) $mixed();
        }

        if ($mixed instanceof BooleanType) {
            return ($mixed->isTrue()) ? 'true' : 'false';
        }

        if ($mixed instanceof Collection) {
            //Handle as array.
            $mixed = $mixed->toArray();
        }

        $type = strtolower(gettype($mixed));
        switch ($type) {
            case 'string':
            case 'integer':
            case 'float':
            case 'double':
                return (string) $mixed;
            case 'boolean':
                return ($mixed) ? 'true' : 'false';
            case 'array':
                return implode(', ', $mixed);
            case 'object':
                if (method_exists($mixed, '__toString')) {
                    return (string) $mixed;
                }

                throw new InvalidTransformationException($type, static::class);
            case 'resource':
                return get_resource_type($mixed);
            case 'null':
            default:
                throw new InvalidTransformationException($type, static::class);
        }
    }
}