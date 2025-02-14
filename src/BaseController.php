<?php

declare(strict_types=1);

namespace GustavoSantarosa\ControllerBasicsExtension;

use GustavoSantarosa\HandlerBasicsExtension\Traits\ApiResponseTrait;
use GustavoSantarosa\ServiceBasicsExtension\BaseService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;
    use ApiResponseTrait;

    protected $defaultResource;
    protected BaseService $defaultService;

    public function index(): JsonResponse
    {
        $this->checkIncludes();

        return $this->okResponse(
            data: $this->defaultResource::collection($this->defaultService->index()),
            allowedInclude: true,
            allowedFilters: true,
        );
    }

    public function show(int $id): JsonResponse
    {
        $this->checkIncludes();

        $result = $this->defaultService->show($id);

        return $this->okResponse(
            data: new $this->defaultResource($result),
            allowedInclude: true,
        );
    }

    public function store(): JsonResponse
    {
        $this->checkIncludes();

        return $this->okResponse(
            message: __('messages.successfully.created'),
            data: new $this->defaultResource($this->defaultService->store()),
            allowedInclude: true,
        );
    }

    public function update(int $id): JsonResponse
    {
        $this->checkIncludes();

        return $this->okResponse(
            message: __('messages.successfully.updated'),
            data: new $this->defaultResource($this->defaultService->update($id)),
            allowedInclude: true,
        );
    }

    public function destroy(int $id): JsonResponse
    {
        if (!$this->defaultService->destroy($id)) {
            return response()->json([
                'message' => "Não foi possivel deletar o registro {$id}!",
            ]);
        }

        return $this->okResponse(
            message: __('messages.successfully.deleted_with_id', ['id' => $id]),
        );
    }

    public function restore(int $id): JsonResponse
    {
        $this->defaultService->restore($id);

        return $this->okResponse(
            message: __('messages.successfully.restore_with_id', ['id' => $id]),
        );
    }

    protected string $service  = '';
    protected string $resource = '';

    public function booted(): void
    {
        throw_if(
            empty($this->resource),
            new \Exception('Resource must be defined in the '.request()->route()->getAction('controller').'.')
        );

        throw_if(
            empty($this->service),
            new \Exception('Service must be defined in the '.request()->route()->getAction('controller').'.')
        );

        $this->defaultResource = $this->resource;
        $this->defaultService  = app($this->service);
        $this->setAllowedIncludes($this->allowedIncludes);
    }
}
