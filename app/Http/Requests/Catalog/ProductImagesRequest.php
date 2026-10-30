<?php

namespace App\Http\Requests\Catalog;

use Illuminate\Foundation\Http\FormRequest;

class ProductImagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // el permiso (…productos.images) lo valida el middleware de la ruta
    }

    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:6'],
            // Sin SVG (puede llevar scripts). Máximo 2 MB por imagen.
            'images.*' => ['file', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'images.required' => 'Elige al menos una imagen.',
            'images.*.mimes' => 'Las imágenes deben ser PNG, JPG o WEBP.',
            'images.*.max' => 'Cada imagen puede pesar hasta 2 MB.',
        ];
    }
}
