<?php
// Prueba del calendario sin Laravel:  php packages/itca/horas-sociales/pruebas/calendario.php
require __DIR__.'/../src/Support/CalendarioSalvadoreno.php';
use Itca\HorasSociales\Support\CalendarioSalvadoreno as C;
$fallos = 0;
$ok = function ($cond, $msg) use (&$fallos) { echo ($cond ? "  ok   " : "  FALLA ").$msg."\n"; if (!$cond) $fallos++; };
// Pascua: fechas conocidas
foreach (['2024'=>'2024-03-31','2025'=>'2025-04-20','2026'=>'2026-04-05','2027'=>'2027-03-28','2028'=>'2028-04-16','2030'=>'2030-04-21'] as $a=>$f)
  $ok(C::pascua((int)$a)->format('Y-m-d') === $f, "Pascua $a = $f");
// Asuetos 2026 (Jueves/Viernes/Sábado Santo 2, 3, 4 de abril)
foreach (['2026-04-02','2026-04-03','2026-04-04','2026-05-01','2026-08-06','2026-09-15','2026-11-02','2026-12-25','2027-01-01'] as $f)
  $ok(C::periodoQueCubre($f) !== null, "$f no hábil (".(C::periodoQueCubre($f)['nombre'] ?? '-').")");
// Vacaciones completas
foreach (['2026-03-30','2026-04-05','2026-08-01','2026-08-03','2026-12-24','2026-12-31'] as $f)
  $ok(C::periodoQueCubre($f) !== null, "$f no hábil (".(C::periodoQueCubre($f)['nombre'] ?? '-').")");
// Días hábiles
foreach (['2026-03-29','2026-04-06','2026-07-31','2026-08-07','2026-11-03','2026-12-23','2027-01-02'] as $f)
  $ok(C::periodoQueCubre($f) === null, "$f hábil");
echo $fallos ? "\n$fallos fallas\n" : "\nTodo correcto\n";
exit($fallos ? 1 : 0);
