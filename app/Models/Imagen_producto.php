<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Imágenes de un producto (tabla imagen_producto).
 *
 * `imagen_url` se guarda RELATIVA al host (`/storage/productos/images/{id}/x.jpg`)
 * para que no quede atada al dominio del momento de la carga. En la base
 * conviven filas históricas con URL absoluta: los helpers de abajo aceptan
 * ambas formas.
 */
class Imagen_producto extends Model
{
    const PREFIJO_MINIATURA = 'thumb_300x300_';
    const SIN_IMAGEN = '/assets/img/photos/no-image-featured-image.png';

    protected $table = 'imagen_producto';

    protected $appends = ['miniatura'];

    public function getMiniaturaAttribute(): string
    {
        return static::miniatura((string) $this->imagen_url, (int) $this->productos_id);
    }

    /**
     * URL de la miniatura 300x300 que se genera junto a la imagen original
     * (mismo directorio, prefijo en el nombre de archivo). Las URLs que no son de
     * una imagen de producto (placeholder "sin imagen") se devuelven tal cual.
     */
    public static function miniatura(string $url, int $productoId): string
    {
        $carpeta = '/productos/images/'.$productoId.'/';
        $pos = strrpos($url, $carpeta);
        if ($pos === false) {
            return $url;
        }
        $inicioNombre = $pos + strlen($carpeta);
        return substr($url, 0, $inicioNombre).self::PREFIJO_MINIATURA.substr($url, $inicioNombre);
    }

    /** Completa el host de la instancia solo si la URL es relativa (consumidores externos: API). */
    public static function absoluta(string $url): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        return url($url);
    }

    /** `public/productos/images/9/x.png` (path del disco local) -> `/storage/productos/images/9/x.png`. */
    public static function relativaDesdePath(string $path): string
    {
        return '/storage/'.preg_replace('#^public/#', '', $path);
    }
}
