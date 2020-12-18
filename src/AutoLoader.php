<?php
namespace CodeAnti\Swoft\Pgsql;

use function bean;
use ReflectionException;
use Swoft\SwoftComponent;

/**
 * Class AutoLoader
 *
 * @since 2.0
 */
class AutoLoader extends SwoftComponent
{
    /**
     * @return array
     * @throws ReflectionException
     * @throws ContainerException
     */
    public function beans(): array
    {
        return [
            'pgsql'      => [
                'class'  => PgsqlDb::class,
            ],
            'pgsql.pool' => [
                'class'   => Pool::class,
                'pgsqlDb' => bean('pgsql')
            ]
        ];
    }
}
