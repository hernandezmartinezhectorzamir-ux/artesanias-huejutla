#!/usr/bin/env bash
# Prueba de humo: verifica que las URL principales respondan y contengan
# los elementos clave. Uso: bash scripts/prueba-humo.sh https://sitio
set -u
BASE="${1:?Indica la URL base del sitio}"
FALLAS=0

comprobar() { # ruta  código_esperado  texto_esperado(opcional)
  local ruta="$1" esperado="$2" texto="${3:-}"
  local cuerpo codigo
  cuerpo=$(mktemp)
  codigo=$(curl -s -L -o "$cuerpo" -w "%{http_code}" --max-time 30 "$BASE$ruta")
  if [ "$codigo" != "$esperado" ]; then
    echo "FALLA  $ruta → HTTP $codigo (esperado $esperado)"; FALLAS=$((FALLAS+1))
  elif [ -n "$texto" ] && ! grep -q "$texto" "$cuerpo"; then
    echo "FALLA  $ruta → no contiene «$texto»"; FALLAS=$((FALLAS+1))
  else
    echo "OK     $ruta ($codigo)"
  fi
  rm -f "$cuerpo"
}

comprobar "/"                                  200 "Artesanías Auténticas de la Huasteca Hidalguense"
comprobar "/"                                  200 "Artesanías de Huejutla, Hidalgo | 100% Hechas a Mano"
comprobar "/tienda/"                           200 "products"
comprobar "/categoria/cesteria-de-palma/"      200 "Cestería de palma"
comprobar "/producto/canasta-de-palma-tejida/" 200 "Canasta de Palma Tejida"
comprobar "/blog/"                             200 "ah-tarjeta-entrada"
comprobar "/mesa-de-ayuda/"                    200 "ah_accion"
comprobar "/servicios/"                        200 "ah-servicio"
comprobar "/preguntas-frecuentes/"             200 "FAQPage"
comprobar "/sitemap_index.xml"                 200 "sitemap"
comprobar "/wp-json/ah/v1/salud"               200 '"estado":"ok"'
comprobar "/pagina-que-no-existe-ci/"          404

echo "Fallas: $FALLAS"
exit $FALLAS
