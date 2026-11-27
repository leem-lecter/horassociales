{{-- Estilos del módulo. Se incluyen dentro de la sección, así funcionan también con la plantilla del profesor. --}}
<style>
/* Estilos del módulo, tomados del prototipo HTML.
   Idea de diseño: un cuaderno de control. Papel frío, tinta azul de lapicero como único acento
   (las hojas oficiales se llenan en azul), y cada pantalla rotulada con el código de su formulario. */
:root{
  --papel:#f3f5fa; --hoja:#ffffff; --tinta:#141b2d; --gris:#586176; --linea:#d7ddea;
  --azul:#1e3fc0; --azul-suave:#e7ecff; --sobre-azul:#ffffff;
  --ok:#1b7447; --ok-suave:#e2f4ea; --aviso:#8f5600; --aviso-suave:#fdf0d8; --mal:#b02a22; --mal-suave:#fde7e5;
  --display:"Archivo","Arial Narrow",Arial,sans-serif;
  --texto:"IBM Plex Sans","Segoe UI",Roboto,Arial,sans-serif;
  --mono:"IBM Plex Mono",ui-monospace,Consolas,monospace;
}
@media (prefers-color-scheme:dark){
  :root:not([data-theme="light"]){
    color-scheme:dark;
    --papel:#0e1320; --hoja:#161c2c; --tinta:#e7ebf5; --gris:#9aa4bb; --linea:#2a334d;
    --azul:#93a8ff; --azul-suave:#1d2850; --sobre-azul:#0e1320;
    --ok:#6fd3a0; --ok-suave:#143524; --aviso:#f0b860; --aviso-suave:#3a2c10; --mal:#ff9d94; --mal-suave:#431b18;
  }
}
:root[data-theme="dark"]{
  color-scheme:dark;
  --papel:#0e1320; --hoja:#161c2c; --tinta:#e7ebf5; --gris:#9aa4bb; --linea:#2a334d;
  --azul:#93a8ff; --azul-suave:#1d2850; --sobre-azul:#0e1320;
  --ok:#6fd3a0; --ok-suave:#143524; --aviso:#f0b860; --aviso-suave:#3a2c10; --mal:#ff9d94; --mal-suave:#431b18;
}
*{box-sizing:border-box}
html,body{margin:0}
body{background:var(--papel);color:var(--tinta);font:400 15px/1.5 var(--texto)}
h1,h2,h3{font-family:var(--display);margin:0;text-wrap:balance;line-height:1.2}
h1{font-size:26px} h2{font-size:18px} h3{font-size:15px}
p{margin:0}
a{color:var(--azul)}
button,input,select,textarea{font:inherit;color:inherit}
:focus-visible{outline:2px solid var(--azul);outline-offset:2px}
.codigo{font:500 11px var(--mono);letter-spacing:.04em;color:var(--azul);background:var(--azul-suave);padding:2px 7px;border-radius:4px;white-space:nowrap}
.gris{color:var(--gris)} .num{font-variant-numeric:tabular-nums}

/* Marco */
.marco{display:grid;grid-template-columns:236px minmax(0,1fr);min-height:100vh}
.marco>*,.dos>*{min-width:0}
.lateral{background:var(--hoja);border-right:1px solid var(--linea);padding:20px 14px;display:flex;flex-direction:column;gap:18px}
.marca{display:flex;flex-direction:column;gap:2px;padding:0 8px}
.marca b{font:700 17px var(--display)} .marca span{font-size:12px;color:var(--gris)}
.nav{display:flex;flex-direction:column;gap:2px}
.nav a{display:flex;flex-direction:column;gap:1px;padding:8px 10px;border-radius:7px;text-decoration:none;color:var(--tinta);font-weight:500}
.nav a small{font:500 10.5px var(--mono);color:var(--gris)}
.nav a:hover{background:var(--papel)}
.nav a[aria-current="page"]{background:var(--azul-suave);color:var(--azul)}
.nav a[aria-current="page"] small{color:var(--azul)}
.quien{margin-top:auto;padding:12px 10px;border-top:1px solid var(--linea);display:flex;flex-direction:column;gap:8px;font-size:13px}
.principal{padding:28px clamp(16px,4vw,44px) 60px;min-width:0}
.cabecera{display:flex;flex-wrap:wrap;align-items:baseline;gap:10px 14px;margin-bottom:6px}
.bajada{color:var(--gris);max-width:68ch;margin-bottom:22px}
.pila{display:flex;flex-direction:column;gap:18px;max-width:1040px}

/* Piezas */
.hoja{background:var(--hoja);border:1px solid var(--linea);border-radius:10px;padding:20px}
.hoja>header{display:flex;flex-wrap:wrap;align-items:center;gap:8px 12px;margin-bottom:14px}
.rejilla{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px}
.ancho{grid-column:1/-1}
.campo{display:flex;flex-direction:column;gap:5px;min-width:0}
.campo>span{font-size:13px;font-weight:500}
.campo small{font-size:12px;color:var(--gris)}
.campo .error{color:var(--mal);font-weight:500}
input,select,textarea{width:100%;background:var(--hoja);border:1px solid var(--linea);border-radius:7px;padding:8px 10px;min-height:38px}
textarea{min-height:76px;resize:vertical}
input:disabled,select:disabled,textarea:disabled{background:var(--papel);color:var(--gris)}
input[type=checkbox],input[type=radio]{width:auto;min-height:0;accent-color:var(--azul)}
input.con-error{border-color:var(--mal)}
.boton{display:inline-flex;align-items:center;justify-content:center;gap:6px;border:1px solid var(--azul);background:var(--azul);color:var(--sobre-azul);border-radius:7px;padding:8px 14px;font-weight:600;cursor:pointer;text-decoration:none;min-height:38px}
.boton.suave{background:transparent;color:var(--azul)}
.boton.plano{border-color:var(--linea);background:transparent;color:var(--tinta);font-weight:500}
.boton.chico{padding:4px 10px;min-height:30px;font-size:13px}
.boton:disabled{opacity:.5;cursor:not-allowed}
.fila{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
.pastilla{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;padding:2px 9px;border-radius:99px;white-space:nowrap;background:var(--papel);color:var(--gris);border:1px solid var(--linea)}
.pastilla::before{content:"";width:7px;height:7px;border-radius:50%;background:currentColor}
.pastilla.ok{background:var(--ok-suave);color:var(--ok);border-color:transparent}
.pastilla.aviso{background:var(--aviso-suave);color:var(--aviso);border-color:transparent}
.pastilla.mal{background:var(--mal-suave);color:var(--mal);border-color:transparent}
.nota{border-left:3px solid var(--azul);background:var(--azul-suave);padding:10px 14px;border-radius:0 7px 7px 0;font-size:14px}
.nota.aviso{border-color:var(--aviso);background:var(--aviso-suave)}
.nota.mal{border-color:var(--mal);background:var(--mal-suave)}
.nota.ok{border-color:var(--ok);background:var(--ok-suave)}
.desliza{overflow-x:auto}
table{border-collapse:collapse;width:100%;font-size:14px}
th,td{text-align:left;padding:8px 10px;border-bottom:1px solid var(--linea);vertical-align:top}
th{font-size:12px;font-weight:600;color:var(--gris);white-space:nowrap}
td.num,th.num{text-align:right}
tfoot td{font-weight:600;border-bottom:0}

/* Panel */
.cifras{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px}
.cifra{display:flex;flex-direction:column;gap:2px}
.cifra b{font:700 30px/1.1 var(--display);font-variant-numeric:tabular-nums}
.cifra span{font-size:13px;color:var(--gris)}
.barra{display:flex;height:14px;border-radius:99px;overflow:hidden;background:var(--papel);border:1px solid var(--linea)}
.barra i{display:block;height:100%}
.barra .a{background:var(--azul)}
.barra .p{background:repeating-linear-gradient(135deg,var(--azul) 0 4px,var(--azul-suave) 4px 8px)}
.barra .v{background:var(--ok)}
.leyenda{display:flex;flex-wrap:wrap;gap:6px 16px;font-size:12.5px;color:var(--gris)}
.leyenda i{display:inline-block;width:10px;height:10px;border-radius:3px;margin-right:5px;vertical-align:-1px}
.dos{display:grid;grid-template-columns:minmax(0,340px) minmax(0,1fr);gap:18px;align-items:start}
.pestanas{display:flex;gap:6px;flex-wrap:wrap}
.pestanas button{border:1px solid var(--linea);background:transparent;border-radius:99px;padding:5px 12px;cursor:pointer;font-weight:500}
.pestanas button[aria-pressed="true"]{background:var(--azul);border-color:var(--azul);color:var(--sobre-azul)}
.horario input{min-width:92px;padding:6px 8px}
.lista{margin:0;padding-left:20px;display:flex;flex-direction:column;gap:6px;max-width:70ch}

/* Ingreso */
.ingreso{min-height:100vh;display:grid;place-items:center;padding:24px 16px}
.ingreso .hoja{width:100%;max-width:400px;display:flex;flex-direction:column;gap:16px}
.demo{display:flex;flex-direction:column;gap:8px;border-top:1px dashed var(--linea);padding-top:14px;font-size:13px}

#aviso-flotante{position:fixed;left:50%;bottom:22px;transform:translateX(-50%);background:var(--tinta);color:var(--papel);padding:9px 16px;border-radius:8px;font-size:14px;max-width:calc(100% - 32px);opacity:0;pointer-events:none;transition:opacity .2s}
#aviso-flotante.ver{opacity:1}
@media (prefers-reduced-motion:reduce){#aviso-flotante{transition:none}}

@media (max-width:860px){
  .marco{grid-template-columns:minmax(0,1fr)}
  .lateral{border-right:0;border-bottom:1px solid var(--linea);padding:12px 16px;gap:10px}
  .marca{padding:0}
  .nav{flex-direction:row;overflow-x:auto;gap:6px;padding-bottom:4px}
  .nav a{flex:0 0 auto;border:1px solid var(--linea)}
  .quien{margin:0;padding:8px 0 0;flex-direction:row;align-items:center;justify-content:space-between}
  .principal{padding-top:20px}
  .dos{grid-template-columns:1fr}
  h1{font-size:22px}
}

/* Agregados del módulo Laravel */
.nav span.pendiente{display:flex;flex-direction:column;gap:1px;padding:8px 10px;border-radius:7px;color:var(--gris);opacity:.65;cursor:default}
.nav span.pendiente small{font:500 10.5px var(--mono)}
.quien form{margin:0}
@media (max-width:860px){.nav span.pendiente{flex:0 0 auto;border:1px dashed var(--linea)}}

/* M4 · Registro diario */
[hidden]{display:none !important}
.pestanas a{border:1px solid var(--linea);border-radius:99px;padding:5px 12px;font-weight:500;text-decoration:none;color:var(--tinta)}
.pestanas a:hover{background:var(--papel)}
.pestanas a[aria-current="page"]{background:var(--azul);border-color:var(--azul);color:var(--sobre-azul)}
td form{margin:0}
input[type=file]{padding:6px}
input[type=file].con-error{border-color:var(--mal)}
.dias-no-habiles{font-size:13px}
.anio-reglas summary{cursor:pointer;font-weight:600;width:max-content}
.dias-no-habiles summary{cursor:pointer;color:var(--azul);font-weight:500;width:max-content}
.dias-no-habiles p{margin-top:8px}
.dias-no-habiles ul{margin:4px 0 0;padding-left:18px;max-height:220px;overflow:auto;display:flex;flex-direction:column;gap:2px}
.estado-acciones{display:flex;flex-direction:column;align-items:flex-start;gap:6px}
/* En celular la columna única no debe crecer con la tabla: la tabla se desliza dentro de su hoja. */
@media (max-width:860px){.dos{grid-template-columns:minmax(0,1fr)}}
/* M6 · Vista del profesor */
.resolver{display:flex;flex-direction:column;gap:6px;min-width:210px;max-width:320px;margin:0}
.resolver input{min-height:32px;padding:5px 9px;font-size:13px}
.resolver .error-resolver{color:var(--mal);font-size:12px;font-weight:500}
.revision-item{justify-content:space-between;align-items:flex-start;padding:12px 0;border-top:1px solid var(--linea)}
.revision-item:first-of-type{border-top:0;padding-top:0}
.datos{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:10px 18px;margin:0}
.datos div{min-width:0}
.datos dt{font-size:12px;font-weight:600;color:var(--gris)}
.datos dd{margin:0;overflow-wrap:anywhere}
.hoja h3{margin:18px 0 8px}
.buscar{max-width:420px}
.buscar input{min-height:34px}
.paginas{justify-content:space-between;margin-top:12px;font-size:14px}
</style>
