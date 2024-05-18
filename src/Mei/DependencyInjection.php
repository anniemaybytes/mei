<?php

declare(strict_types=1);

namespace Mei;

use ArrayAccess;
use DI;
use Exception;
use Mei\Entity\ICacheable;
use Mei\Model\FilesMap;
use Psr\Container\ContainerInterface as Container;

/**
 * Class DependencyInjection
 *
 * @package Mei
 */
final class DependencyInjection
{
    /** @throws Exception */
    public static function build(array $baseDefinitions, ArrayAccess $config, bool $enableCompilation): Container
    {
        // prepare builder
        $builder = new DI\ContainerBuilder();
        $builder->useAttributes(true);
        if ($enableCompilation) {
            $builder->enableCompilation(BASE_ROOT);
        }
        $builder->addDefinitions($baseDefinitions);
        $builder->addDefinitions(self::getModelsDefinitions());

        // run build
        $di = $builder->build();

        // dynamic definitions
        $di->set('config', $config);
        $di->set('ob_level', ob_get_level());

        return $di;
    }

    private static function getModelsDefinitions(): array
    {
        return [
            FilesMap::class => DI\autowire()
                ->constructorParameter(
                    'entityBuilder',
                    DI\value(
                        function (ICacheable $c) {
                            return new Entity\FilesMap($c);
                        }
                    )
                ),
        ];
    }
}
