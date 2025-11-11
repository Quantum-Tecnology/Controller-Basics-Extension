<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Contracts;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface ControllerInterface
{
    public function index(): JsonResponse | StreamedResponse;

    public function show(int $id): JsonResponse;

    public function store(): JsonResponse;

    public function update(int $id): JsonResponse;

    public function destroy(int $id): JsonResponse;

    public function restore(int $id): JsonResponse;
}
