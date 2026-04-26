<?php

declare(strict_types=1);

namespace App;

use Override;
use ReflectionClass;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollection;

class Kernel extends BaseKernel
{
    use MicroKernelTrait {
        registerContainerConfiguration as private baseRegisterContainerConfiguration;
        loadRoutes as private baseLoadRoutes;
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $this->baseRegisterContainerConfiguration($loader);

        $clientDir = $this->resolveClientDir();

        if (null === $clientDir) {
            return;
        }

        $customPackages = $clientDir.'/config/packages-custom.yaml';
        if (is_file($customPackages)) {
            $loader->load($customPackages);
        }

        $customServices = $clientDir.'/config/services-custom.yaml';
        if (is_file($customServices)) {
            $loader->load($customServices);
        }

        if (is_dir($clientDir.'/templates')) {
            $loader->load(static function ($container) use ($clientDir): void {
                $container->loadFromExtension('twig', [
                    'paths' => [$clientDir.'/templates' => null],
                ]);
            });
        }
    }

    public function loadRoutes(LoaderInterface $loader): RouteCollection
    {
        $collection = $this->baseLoadRoutes($loader);

        $clientDir = $this->resolveClientDir();

        if (null === $clientDir) {
            return $collection;
        }

        $customRoutes = $clientDir.'/config/routes-custom.yaml';
        if (is_file($customRoutes)) {
            $routeLoader = $loader->getResolver()->resolve($customRoutes);
            if (false !== $routeLoader) {
                $collection->addCollection($routeLoader->load($customRoutes));
            }
        }

        return $collection;
    }

    #[Override]
    protected function getKernelParameters(): array
    {
        $parameters = parent::getKernelParameters();

        $clientDir = $this->resolveClientDir();
        if (null !== $clientDir) {
            $parameters['aurora.client_dir'] = $clientDir;
        }

        return $parameters;
    }

    private function resolveClientDir(): ?string
    {
        $kernelFile = new ReflectionClass(static::class)->getFileName();
        $dir = dirname((string) $kernelFile);

        while (($parent = dirname($dir)) !== $dir) {
            $dir = $parent;

            if (str_contains(str_replace('\\', '/', $dir), '/vendor/')) {
                continue;
            }

            if (!is_file($dir.'/composer.json')) {
                continue;
            }

            if (realpath($dir) === realpath($this->getProjectDir())) {
                return null;
            }

            return $dir;
        }

        return null;
    }
}
