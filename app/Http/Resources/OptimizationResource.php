<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OptimizationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'description' => $this->description,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'total_periods' => $this->total_periods,
            'discount_rate' => (float) $this->discount_rate,
            'initial_balance' => (float) $this->initial_balance,
            'nb_must_take_one' => $this->nb_must_take_one,
            'result' => $this->whenLoaded('result', function () {
                return [
                    'id' => $this->result->id,
                    'npv' => (float) $this->result->npv,
                    'final_balance' => (float) $this->result->final_balance,
                    'initial_balance' => (float) $this->result->initial_balance,
                    'total_periods' => $this->result->total_periods,
                    'total_projects' => $this->result->total_projects,
                    'projects_selected' => $this->result->projects_selected,
                    'status' => $this->result->status,
                ];
            }),
        ];
    }
}
