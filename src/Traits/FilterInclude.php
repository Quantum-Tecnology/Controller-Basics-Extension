<?php

namespace GustavoSantarosa\ServiceBasicsExtension\Traits;

trait FilterInclude
{
    private $includeCallable;

    private function include(): void
    {
        if (!request()->include) {
            return;
        }

        if (!is_callable($this->includeCallable)) {
            $this->setInclude();
        }

        $callback = $this->includeCallable;

        $this->customQuery = $callback($this->defaultQuery());
    }

    /**
     * With this function it is possible to pass a callback to include the custom relationships.
     */
    protected function setInclude(?callable $callback = null): void
    {
        if (is_callable($callback)) {
            $this->includeCallable = $callback;

            return;
        }

        $this->includeCallable = function ($query) {
            collect(explode(',', request()->include))
                ->each(function ($relation) use ($query) {
                    $relation = collect(explode(';', $relation))
                        ->transform(function ($collum) use ($query) {
                            if (str_contains($collum, 'count')) {
                                $query->withCount(explode(':', $collum)[0]);

                                return;
                            }

                            return $collum;
                        })->filter();

                    if ($relation->isEmpty()) {
                        return;
                    }

                    $query->with($relation->implode(','));
                });

            return $query;
        };

        return;
    }
}
