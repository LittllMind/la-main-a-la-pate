<?php

namespace App\Http\Requests\Vinyle;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVinyleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole(['admin', 'employe']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $vinyleId = $this->route('vinyle')->id ?? null;

        return [
            'reference' => 'required|string|max:50|unique:vinyles,reference,' . $vinyleId,
            'artiste' => 'required|string|max:255',
            'modele' => 'required|string|max:255',
            'genre' => 'nullable|string|max:100',
            'style' => 'nullable|string|max:100',
            'prix' => 'required|numeric|min:0',
            'quantite' => 'required|integer|min:0',
            'seuil_alerte' => 'required|integer|min:1',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'delete_photos' => 'nullable|array',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reference.required' => 'La référence est obligatoire.',
            'reference.unique' => 'Cette référence existe déjà.',
            'reference.max' => 'La référence ne doit pas dépasser 50 caractères.',
            'artiste.required' => 'L\'artiste est obligatoire.',
            'modele.required' => 'Le modèle est obligatoire.',
            'prix.required' => 'Le prix est obligatoire.',
            'prix.numeric' => 'Le prix doit être un nombre.',
            'prix.min' => 'Le prix ne peut pas être négatif.',
            'quantite.required' => 'La quantité est obligatoire.',
            'quantite.integer' => 'La quantité doit être un nombre entier.',
            'quantite.min' => 'La quantité ne peut pas être négative.',
            'seuil_alerte.required' => 'Le seuil d\'alerte est obligatoire.',
            'seuil_alerte.min' => 'Le seuil d\'alerte doit être au moins 1.',
            'photos.*.image' => 'Le fichier doit être une image.',
            'photos.*.mimes' => 'Les formats acceptés sont : jpeg, png, jpg, webp.',
            'photos.*.max' => 'Chaque image ne doit pas dépasser 5 Mo.',
        ];
    }
}
