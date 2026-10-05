<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['keywords', 'description', 'response', 'quick_button', 'forward_to_admin', 'priority'])]
class ChatbotRule extends Model
{
    protected function casts(): array
    {
        return ['forward_to_admin' => 'boolean'];
    }

    /**
     * @return list<string>
     */
    public function keywordList(): array
    {
        return array_values(array_filter(array_map(fn ($k) => mb_strtolower(trim($k)), explode(',', $this->keywords))));
    }
}
