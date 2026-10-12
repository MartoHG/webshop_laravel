<?php

namespace Tests\Unit;

use App\View\EstadoVisiblePago;
use App\View\OpcionPagoVisible;
use PHPUnit\Framework\TestCase;

class OpcionPagoVisibleTest extends TestCase
{
    public function test_la_cuota_se_redondea_hacia_arriba_al_centavo(): void
    {
        $this->assertSame('96274.51', OpcionPagoVisible::importeDeCuota('288823.53', 3));
        $this->assertSame('416.67', OpcionPagoVisible::importeDeCuota('1250.00', 3));
        $this->assertSame('33.34', OpcionPagoVisible::importeDeCuota('100.00', 3));
        $this->assertSame('238.10', OpcionPagoVisible::importeDeCuota('1428.58', 6));
        $this->assertSame('1000.00', OpcionPagoVisible::importeDeCuota('1000.00', 1));
    }

    public function test_el_detalle_muestra_un_pago_o_las_cuotas_con_su_importe(): void
    {
        $this->assertSame('1 pago', OpcionPagoVisible::detalle(1, '1250.00'));
        $this->assertSame('3 cuotas de $416,67', OpcionPagoVisible::detalle(3, '1250.00'));
    }

    public function test_la_etiqueta_describe_cada_modalidad(): void
    {
        $this->assertSame('Débito', OpcionPagoVisible::etiqueta('debito', 1));
        $this->assertSame('Dinero en Mercado Pago', OpcionPagoVisible::etiqueta('dinero_en_cuenta', 1));
        $this->assertSame('Crédito', OpcionPagoVisible::etiqueta('credito', 1));
        $this->assertSame('Crédito en 6 cuotas', OpcionPagoVisible::etiqueta('credito', 6));
    }

    public function test_un_motivo_desconocido_no_se_explica(): void
    {
        $this->assertSame('La tarjeta no tiene saldo o límite suficiente.', EstadoVisiblePago::motivoDeRechazo('cc_rejected_insufficient_amount'));
        $this->assertNull(EstadoVisiblePago::motivoDeRechazo('error_api_400'));
        $this->assertNull(EstadoVisiblePago::motivoDeRechazo(null));
    }
}
