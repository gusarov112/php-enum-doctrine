<?php
declare(strict_types=1);

namespace Gusarov112\PhpEnumDoctrine\DBAL;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Exception;
use Gusarov112\Enum\Enum;

/**
 * @package WL\CommonBundle\DBAL\Type
 */
abstract class EnumType extends Type
{
    /**
     * @return string Enum class name
     */
    abstract public function getEnumClassName(): string;

    /**
     * Gets the SQL declaration snippet for a field of this type.
     *
     * @param array $fieldDeclaration The field declaration.
     * @param AbstractPlatform $platform The currently used database platform.
     *
     * @return string
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        /** @var Enum $enumClass */
        $enumClass = $this->getEnumClassName();
        $phpEnumValues = array_values($enumClass::toArray());
        $currentDbEnumValues = $fieldDeclaration['currentDbEnumValues'] ?? [];
        $resultEnumValues = array_merge($currentDbEnumValues, array_diff($phpEnumValues, $currentDbEnumValues));

        return sprintf('ENUM(%s)', '"'.implode('","', $resultEnumValues).'"');
    }

    /**
     * @param Enum $value
     * @param AbstractPlatform $platform
     * @return mixed
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return is_null($value) ? null : $value->getValue();
    }

    /**
     * @param mixed $value
     * @param AbstractPlatform $platform
     * @return Enum
     * @throws ConversionException
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $result = null;
        if (!is_null($value)) {
            $className = $this->getEnumClassName();
            try {
                $result = new $className($this->castEnumValue($value));
            } catch (Exception $e) {
                throw new ConversionException($e->getMessage(), $e->getCode(), $e);
            }
        }

        return $result;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }

    protected function castEnumValue($value)
    {
        return (string)$value;
    }
}
