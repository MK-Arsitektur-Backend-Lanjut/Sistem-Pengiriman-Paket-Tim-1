<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWarehouseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'warehouse_name' => 'required|string|max:100',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'current_load' => 'nullable|integer|min:0',
            'status' => 'nullable|in:active,inactive',
            'hub_id' => 'nullable|exists:hubs,id',
        ];
    }

    /**
     * Get custom message for validation rules.
     */
    public function messages(): array
    {
        return [
            'warehouse_name.required' => 'Nama gudang harus diisi',
            'location.required' => 'Lokasi gudang harus diisi',
            'capacity.required' => 'Kapasitas gudang harus diisi',
            'capacity.integer' => 'Kapasitas harus berupa angka',
            'capacity.min' => 'Kapasitas minimal 1 unit',
            'current_load.integer' => 'Beban saat ini harus berupa angka',
            'current_load.min' => 'Beban saat ini tidak bisa negatif',
            'hub_id.exists' => 'Hub yang dipilih tidak valid',
        ];
    }
}
