<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Request;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Author;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Comment;

final class PostRequest extends FormRequest
{
    public function rules(): array
    {

        return [
            'title'     => ['required'],
            'meta'      => ['nullable', 'array'],
            'author_id' => [
                'nullable',
                Rule::requiredIf(fn (): bool => !$this->input('author')),
                'exists:' . Author::class . ',id',
            ],
            'author.name' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('author')),
                'max:70',
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
            'comments.*.id' => [
                'nullable',
                Rule::exists(Comment::class, 'id'),
            ],
            'comments.*.body' => [
                'required',
                'string',
                'max:1000',
            ],
            'comments.*.likes.*.like' => [
                'required',
                'numeric',
                'min:1',
                'max:5',
            ],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
