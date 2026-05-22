#!/usr/bin/env bash
# Download SVG logos for all brands from worldvectorlogo CDN
# Run from the project root: bash scripts/download-brand-logos.sh

set -e
DEST="public/assets/img/brands"
mkdir -p "$DEST"

download() {
  local slug="$1"; shift
  local file="$DEST/$slug.svg"
  for url in "$@"; do
    echo -n "  $slug → $url ... "
    if curl -fsSL --max-time 10 -o "$file" "$url" 2>/dev/null; then
      # Verify it's actually SVG content
      if grep -q "<svg" "$file"; then
        echo "OK"
        return 0
      else
        rm -f "$file"
      fi
    fi
    echo "failed, trying next..."
  done
  echo "  ⚠️  $slug: no source found — keeping placeholder"
  return 0
}

echo "Downloading brand logos..."

download alpine \
  "https://cdn.worldvectorlogo.com/logos/alpine-3.svg" \
  "https://cdn.worldvectorlogo.com/logos/alpine-2.svg" \
  "https://cdn.worldvectorlogo.com/logos/alpine.svg"

download audi \
  "https://cdn.worldvectorlogo.com/logos/audi-2.svg" \
  "https://cdn.worldvectorlogo.com/logos/audi.svg"

download bmw \
  "https://cdn.worldvectorlogo.com/logos/bmw.svg" \
  "https://cdn.worldvectorlogo.com/logos/bmw-2.svg"

download bmw-motorrad \
  "https://cdn.worldvectorlogo.com/logos/bmw-motorrad.svg" \
  "https://cdn.worldvectorlogo.com/logos/bmw.svg"

download byd \
  "https://cdn.worldvectorlogo.com/logos/byd-2.svg" \
  "https://cdn.worldvectorlogo.com/logos/byd.svg"

download dacia \
  "https://cdn.worldvectorlogo.com/logos/dacia-3.svg" \
  "https://cdn.worldvectorlogo.com/logos/dacia-2.svg" \
  "https://cdn.worldvectorlogo.com/logos/dacia.svg"

download dongfeng \
  "https://cdn.worldvectorlogo.com/logos/dongfeng.svg" \
  "https://cdn.worldvectorlogo.com/logos/dongfeng-2.svg"

download farizon \
  "https://cdn.worldvectorlogo.com/logos/farizon.svg"

download geely \
  "https://cdn.worldvectorlogo.com/logos/geely-2.svg" \
  "https://cdn.worldvectorlogo.com/logos/geely.svg"

download hyundai \
  "https://cdn.worldvectorlogo.com/logos/hyundai-motor-company.svg" \
  "https://cdn.worldvectorlogo.com/logos/hyundai-3.svg" \
  "https://cdn.worldvectorlogo.com/logos/hyundai.svg"

download mercedes-benz \
  "https://cdn.worldvectorlogo.com/logos/mercedes-benz-9.svg" \
  "https://cdn.worldvectorlogo.com/logos/mercedes-benz-6.svg" \
  "https://cdn.worldvectorlogo.com/logos/mercedes-benz.svg"

download mini \
  "https://cdn.worldvectorlogo.com/logos/mini-2.svg" \
  "https://cdn.worldvectorlogo.com/logos/mini.svg"

download nissan \
  "https://cdn.worldvectorlogo.com/logos/nissan-6.svg" \
  "https://cdn.worldvectorlogo.com/logos/nissan-2.svg" \
  "https://cdn.worldvectorlogo.com/logos/nissan.svg"

download opel \
  "https://cdn.worldvectorlogo.com/logos/opel-2022.svg" \
  "https://cdn.worldvectorlogo.com/logos/opel-3.svg" \
  "https://cdn.worldvectorlogo.com/logos/opel.svg"

download peugeot \
  "https://cdn.worldvectorlogo.com/logos/peugeot-3.svg" \
  "https://cdn.worldvectorlogo.com/logos/peugeot-2.svg" \
  "https://cdn.worldvectorlogo.com/logos/peugeot.svg"

download renault \
  "https://cdn.worldvectorlogo.com/logos/renault-2021.svg" \
  "https://cdn.worldvectorlogo.com/logos/renault-3.svg" \
  "https://cdn.worldvectorlogo.com/logos/renault.svg"

download skoda \
  "https://cdn.worldvectorlogo.com/logos/skoda-3.svg" \
  "https://cdn.worldvectorlogo.com/logos/skoda-2.svg" \
  "https://cdn.worldvectorlogo.com/logos/skoda.svg"

download volkswagen \
  "https://cdn.worldvectorlogo.com/logos/volkswagen-2019.svg" \
  "https://cdn.worldvectorlogo.com/logos/volkswagen-3.svg" \
  "https://cdn.worldvectorlogo.com/logos/volkswagen.svg"

download voyah \
  "https://cdn.worldvectorlogo.com/logos/voyah.svg"

download xpeng \
  "https://cdn.worldvectorlogo.com/logos/xpeng.svg" \
  "https://cdn.worldvectorlogo.com/logos/xpeng-2.svg"

download zeekr \
  "https://cdn.worldvectorlogo.com/logos/zeekr.svg" \
  "https://cdn.worldvectorlogo.com/logos/zeekr-2.svg"

# Caetano Parts and Carplus are custom — no public CDN source
echo "  ℹ️  caetano-parts and carplus: manual logos required"

echo ""
echo "Done. Check $DEST for results."
echo "Commit with: git add $DEST && git commit -m 'chore: real brand SVG logos'"
