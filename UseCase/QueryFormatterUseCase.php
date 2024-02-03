<?php

namespace FpDbTest\UseCase;

use Exception;
use FpDbTest\Enum\DatabaseSpecifierEnum;

class QueryFormatterUseCase
{
    public static function execute(string $query, array $args, mixed $skip): string
    {
        preg_match_all('/\?(.)/u', $query, $matches, PREG_SET_ORDER);
        $sqlQuery = $query;

        foreach ($matches as $key => $item) {
            $value = $args[$key];
            $placeholder = array_shift($item);
            $specifier = trim(end($item));

            $sqlQuery = self::formatBlock(sqlQuery: $sqlQuery, value: $value, placeholder: $placeholder, skip: $skip);
            $value = self::formatValue(value: $value, specifier: $specifier);
            $sqlQuery = preg_replace("/\?($specifier)/u", $value, $sqlQuery, 1);
        }

        return $sqlQuery;
    }

    private static function formatValue(mixed $value, ?string $specifier): mixed
    {
        return match ($specifier) {
            DatabaseSpecifierEnum::INT->value => $value === null ? 'NULL' : (int)$value,
            DatabaseSpecifierEnum::FLOAT->value => $value === null ? 'NULL' : (float)$value,
            DatabaseSpecifierEnum::ARRAY->value => self::arrayBuild($value),
            DatabaseSpecifierEnum::LIST_OR_ID->value => self::idBuild($value),
            default => self::checkType($value),
        };
    }

    private static function formatBlock(string $sqlQuery, mixed $value, string $placeholder, mixed $skip): mixed
    {
        preg_match('/\{(.*?)\}/', $sqlQuery, $matches);
        if (!empty($matches)) {
            $innerText = $matches[1];

            if (str_contains($innerText, $placeholder)) {
                if ($value === $skip) {
                    return preg_replace('/\{([^}]*)\}/', '', $sqlQuery);
                }

                $innerConditionBlocks = preg_match('/\{(.*?)\}/', $innerText);
                if ($innerConditionBlocks) {
                    throw new Exception('Conditional blocks cannot be nested!!!');
                }
                return str_replace(array('{', '}'), '', $sqlQuery);
            }
        }

        return $sqlQuery;
    }

    private static function idBuild(mixed $value): string
    {
        if (is_string($value)) {
            return "`$value`";
        }

        return sprintf('`%s`', implode('`, `', $value));
    }

    private static function arrayBuild(mixed $value): string
    {
        if (self::is_assoc($value)) {
            $result = null;
            foreach ($value as $key => $item) {
                $item = self::checkType($item);
                $result .= "`$key` = $item, ";
            }

            return substr($result, 0, -2);
        }

        $result = null;
        foreach ($value as $item) {
            $item = $item === null ? 'NULL' : $item;
            $result .= is_string($item) ? "'$item'" : $item . ", ";
        }

        return substr($result, 0, -2);
    }

    private static function checkType($value): mixed
    {
        return match (gettype($value)) {
            'string' => "'$value'",
            'integer' => (int)$value,
            'array' => (array)$value,
            'boolean' => (bool)$value,
            'double' => (float)$value,
            'NULL' => 'NULL',
            default => throw new Exception('Invalid parameter type'),
        };
    }

    private static function is_assoc(array $array): bool
    {
        $keys = array_keys($array);
        return array_keys($keys) !== $keys;
    }
}
