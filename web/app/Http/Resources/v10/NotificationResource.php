<?php

namespace App\Http\Resources\v10;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Une entrée du fil de notifications (`notifications` Laravel), pour l'app.
 *
 * `data` est aplati : l'app lit `kind`, `title`, `body` et les identifiants
 * de navigation (`parcel_id`…) au même niveau que `id` et `read`.
 */
class NotificationResource extends JsonResource
{
    public function toArray($request)
    {
        $data = is_array($this->data) ? $this->data : [];

        return array_merge([
            'id' => (string) $this->id,
            'kind' => $data['kind'] ?? 'message',
            'title' => $data['title'] ?? '',
            'body' => $data['body'] ?? '',
            'read' => $this->read_at !== null,
            'created_at' => optional($this->created_at)->format('d M Y, h:i A'),
            /** ISO 8601, pour trier ou relativiser côté app sans reparser le libellé. */
            'created_at_iso' => optional($this->created_at)->toIso8601String(),
        ], collect($data)->except(['kind', 'title', 'body'])->all());
    }
}
