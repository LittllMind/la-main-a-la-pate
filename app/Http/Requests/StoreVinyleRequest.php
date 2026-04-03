<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVinyleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Le middleware auth/role gère l'autorisation
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:50', 'unique:vinyles,reference'],
            'artiste' => ['required', 'string', 'max:255'],
            'modele' => ['nullable', 'string', 'max:255'],
            'genre' => ['nullable', 'string', 'max:100'],
            'style' => ['nullable', 'string', 'max:100'],
            'prix' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'quantite' => ['required', 'integer', 'min:0'],
            'seuil_alerte' => ['nullable', 'integer', 'min:0'],
            'images' => ['nullable', 'array', 'max:3'],
            'images.*' => ['image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reference' => 'référence',
            'artiste' => 'artiste',
            'modele' => 'modèle',
            'genre' => 'genre',
            'style' => 'style',
            'prix' => 'prix',
            'quantite' => 'quantité',
            'seuil_alerte' => 'seuil d\'alerte',
            'images' => 'images',
            'images.*' => 'image',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'reference.required' => 'La référence est obligatoire.',
            'reference.unique' => 'Cette référence existe déjà.',
            'reference.max' => 'La référence ne doit pas dépasser :max caractères.',
            'artiste.required' => 'L\'artiste est obligatoire.',
            'artiste.max' => 'L\'artiste ne doit pas dépasser :max caractères.',
            'modele.max' => 'Le modèle ne doit pas dépasser :max caractères.',
            'genre.max' => 'Le genre ne doit pas dépasser :max caractères.',
            'style.max' => 'Le style ne doit pas dépasser :max caractères.',
            'prix.required' => 'Le prix est obligatoire.',
            'prix.numeric' => 'Le prix doit être un nombre.',
            'prix.min' => 'Le prix ne peut pas être négatif.',
            'prix.max' => 'Le prix est trop élevé.',
            'quantite.required' => 'La quantité est obligatoire.',
            'quantite.integer' => 'La quantité doit être un nombre entier.',
            'quantite.min' => 'La quantité ne peut pas être négative.',
            'seuil_alerte.integer' => 'Le seuil d\'alerte doit être un nombre entier.',
            'seuil_alerte.min' => 'Le seuil d\'alerte ne peut pas être négatif.',
            'images.array' => 'Les images doivent être un tableau.',
            'images.max' => 'Vous ne pouvez pas télécharger plus de :max images.',
            'images.*.image' => 'Le fichier doit être une image.',
            'images.*.mimes' => 'L\'image doit être au format :values.',
            'images.*.max' => 'L\'image ne doit pas dépasser :max kilo-octets.',
        ];
    }
    
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Nettoyer les espaces
        $this->merge([
            'reference' => trim($this->reference ?? ''),
            'artiste' => trim($this->artiste ?? ''),
            'modele' => trim($this->modele ?? ''),
            'genre' => trim($this->genre ?? ''),
            'style' => trim($this->style ?? ''),
            'prix' => $this->prix !== null ? (float) $this->prix : null,
            'quantite' => $this->quantite !== null ? (int) $this->quantite : null,
            'seuil_alerte' => $this->seuil_alerte !== null ? (int) $this->seuil_alerte : 3,
        ]);
    }
}
