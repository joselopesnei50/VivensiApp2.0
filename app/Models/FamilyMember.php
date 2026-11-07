<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FamilyMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'beneficiary_id',
        'name',
        'kinship',
        'birth_date'
    ];

    protected $casts = [
        'birth_date' => 'encrypted',
    ];

    /**
     * Data de nascimento em dd/mm/aaaa. O campo é cifrado e volta como texto
     * ("2015-04-10"), então ->format() nunca funcionou e a tela/PDF mostravam
     * "Nasc.:" vazio.
     */
    public function birthDateBr(): ?string
    {
        $v = $this->birth_date;
        if ($v === null || $v === '') {
            return null;
        }
        try {
            return \Illuminate\Support\Carbon::parse($v)->format('d/m/Y');
        } catch (\Throwable) {
            return null;
        }
    }
}
