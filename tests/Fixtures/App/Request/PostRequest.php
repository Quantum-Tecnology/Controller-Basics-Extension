<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Request;

use Illuminate\Foundation\Http\FormRequest;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Author;

final class PostRequest extends FormRequest
{
    public function rules(): array
    {

        return [
            'title'     => ['required'],
            'meta'      => ['nullable', 'array'],
            'author_id' => [
                'required',
                'exists:' . Author::class . ',id',
            ],
            'tags.*.name' => [
                'required',
                'string',
                'max:1000',
            ],
            'comments.*.tags.*.name' => [
                'required',
                'string',
                'max:1000',
            ],
            'comments.*.body' => [
                'required',
                'string',
                'max:1000',
            ],
            'comments.*.likes.*.like' => [
                'boolean',
                'required',
                'max:1',
                'max:5',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
