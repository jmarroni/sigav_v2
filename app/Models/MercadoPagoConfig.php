<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class MercadoPagoConfig extends Model
{
    protected $table = 'mercadopago_config';

    protected $fillable = ['sucursal_id', 'public_key', 'activo'];

    protected $attributes = [
        'activo' => true,
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function setAccessTokenAttribute(?string $value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getAccessTokenAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /** Token enmascarado para mostrar en la UI sin exponerlo completo. */
    public function tokenEnmascarado(): ?string
    {
        $token = $this->access_token;

        return $token ? '····'.substr($token, -4) : null;
    }
}
