<?php

namespace Tests\Unit;

use App\Servicios\Precios\FormatoMoneda;
use PHPUnit\Framework\TestCase;

class FormatoMonedaTest extends TestCase
{
    public function test_formatea_sin_decimales_cuando_los_centavos_son_cero(): void
    {
        $this->assertSame('$245.500', FormatoMoneda::formatear('245500.00'));
        $this->assertSame('$1.250', FormatoMoneda::formatear('1250.00'));
        $this->assertSame('$100', FormatoMoneda::formatear('100.00'));
        $this->assertSame('$1.000.000', FormatoMoneda::formatear('1000000.00'));
    }

    public function test_formatea_con_coma_decimal_cuando_hay_centavos(): void
    {
        $this->assertSame('$288.823,53', FormatoMoneda::formatear('288823.53'));
        $this->assertSame('$999,99', FormatoMoneda::formatear('999.99'));
        $this->assertSame('$0,50', FormatoMoneda::formatear('0.5'));
    }

    public function test_formatea_cero_y_negativos_sin_perder_centavos(): void
    {
        $this->assertSame('$0', FormatoMoneda::formatear('0'));
        $this->assertSame('$0', FormatoMoneda::formatear('-0.00'));
        $this->assertSame('-$1.250,50', FormatoMoneda::formatear('-1250.50'));
    }
}
