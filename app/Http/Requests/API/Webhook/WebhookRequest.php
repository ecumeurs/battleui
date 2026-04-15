<?php

namespace App\Http\Requests\API\Webhook;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @spec-link [[api_go_webhook_callback]]
 * @spec-link [[api_standard_envelope]]
 */
class WebhookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Webhooks are currently internal/stateless or verified via source IP/token in middleware
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
            'data' => 'required|array',
            'data.match_id' => 'required|uuid|exists:game_matches,id',
            'data.version' => 'required|integer',
            'data.event_type' => 'required|string',
            'data.data' => 'required|array', // The actual BoardState
        ];
    }
}
