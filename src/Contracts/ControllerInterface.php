<?php

namespace QuantumTecnology\ControllerBasicsExtension\Contracts;

use Illuminate\Http\JsonResponse;

interface ControllerInterface
{
    public function index(): JsonResponse;

    public function show(int $id): JsonResponse;

    public function store(): JsonResponse;

    public function update(int $id): JsonResponse;

    public function destroy(int $id): JsonResponse;
    
    public function restore(int $id): JsonResponse;
}
