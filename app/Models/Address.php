<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'zip_code',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Define este endereço como padrão, removendo o padrão anterior.
     */
    public function setAsDefault(): void
    {
        $this->user->addresses()->where('id', '!=', $this->id)->update(['is_default' => false]);
        $this->update(['is_default' => true]);
    }

    /**
     * Retorna o endereço formatado em string.
     */
    public function fullAddress(): string
    {
        return trim(sprintf(
            '%s, %s%s - %s, %s%s',
            $this->street,
            $this->number,
            $this->complement ? ' (' . $this->complement . ')' : '',
            $this->neighborhood,
            $this->city,
            $this->zip_code ? ' CEP: ' . $this->zip_code : '',
        ));
    }
}
