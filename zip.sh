#!/bin/bash
mkdir -p nz-quizmosaic nz-quizmosaic/src/admin # Créer le dossier "nz-quizmosaic"
cp -R public/ nz-quizmosaic.php uninstall.php README.md LICENSE nz-quizmosaic/  # Copier les fichiers et dossiers dans "nz-quizmosaic"
cp src/RestAPIController.php nz-quizmosaic/src/
cp src/admin/*.php nz-quizmosaic/src/admin/
zip -r nz-quizmosaic.zip nz-quizmosaic/
rm -r nz-quizmosaic/