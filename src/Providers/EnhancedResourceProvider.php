<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Providers;

use Illuminate\Routing\ResourceRegistrar as BaseResourceRegistrar;
use Override;

final class EnhancedResourceProvider extends BaseResourceRegistrar
{
    /**
     * Add the default resource routes to the router.
     *
     * @param string $name
     * @param string $controller
     */
    #[Override]
    public function register($name, $controller, array $options = []): void
    {
        parent::register($name, $controller, $options);

        $this->addResourceRestore($name, $controller);
        $this->addResourceSummary($name, $controller);
    }

    /**
     * Add the restore method for a resource.
     *
     * @param string $name
     * @param string $controller
     */
    private function addResourceRestore($name, $controller): void
    {
        $this->router->post("{$name}/{id}/restore", [
            'as'   => "{$name}.restore",
            'uses' => "{$controller}@restore",
        ]);
    }

    /**
     * Add the summary method for a resource.
     *
     * @param string $name
     * @param string $controller
     */
    private function addResourceSummary($name, $controller): void
    {
        $this->router->get("{$name}/summary", [
            'as'   => "{$name}.summary",
            'uses' => "{$controller}@summary",
        ]);
    }
}
