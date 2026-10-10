<?php

namespace Database\Seeders;

use App\Enums\IconoBeneficio;
use App\Models\BeneficioHome;
use Illuminate\Database\Seeder;

class BeneficioHomeSeeder extends Seeder
{
    public function run(): void
    {
        if (BeneficioHome::query()->exists()) {
            return;
        }

        $beneficios = [
            [IconoBeneficio::Envio, 'Envíos a todo el país por expreso'],
            [IconoBeneficio::Pago, 'Pagás recién cuando acordamos el envío'],
            [IconoBeneficio::Local, 'Retiro en el local de Olavarría'],
            [IconoBeneficio::Whatsapp, 'Te atendemos por WhatsApp'],
        ];

        foreach ($beneficios as $orden => [$icono, $texto]) {
            BeneficioHome::query()->create([
                'icono' => $icono,
                'texto' => $texto,
                'orden' => $orden,
                'activo' => true,
            ]);
        }
    }
}
