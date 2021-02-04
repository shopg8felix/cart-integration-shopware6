<?php

namespace Shopgate\Shopware\System\Di;

use Shopgate\Shopware\Exceptions\DiException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Facade
{
    /**
     * self|null
     */
    private static $instance;

    /**
     * ContainerInterface
     */
    private static $myContainer;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        self::$myContainer = $container;
    }

    /**
     * @param string $serviceId
     *
     * @return object
     * @throws DiException
     */
    public static function create(string $serviceId): object
    {
        if (null === self::$instance) {
            throw new DiException('Facade is not instantiated');
        }

        return self::$myContainer->get($serviceId);
    }

    /**
     * @param ContainerInterface $container
     *
     * @return null|Facade
     */
    public static function init(ContainerInterface $container): ?Facade
    {
        if (null === self::$instance) {
            self::$instance = new self($container);
        }

        return self::$instance;
    }
}