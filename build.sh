#!/bin/bash

VERSION="1.0.0"
PLUGIN_SLUG="geo-ip-blocker"
BUILD_DIR="build"
ZIP_NAME="${PLUGIN_SLUG}-${VERSION}.zip"

echo "📦 Building ${PLUGIN_SLUG} v${VERSION}..."

# Navigate to plugin directory
cd geo-ip-blocker/

# Install dependencies
echo "Installing production dependencies..."
composer install --no-dev --optimize-autoloader

# Verify vendor exists
if [ ! -d "vendor/yahnis-elsts/plugin-update-checker" ]; then
    echo "❌ Error: Plugin Update Checker not found in vendor/"
    exit 1
fi

# Go back to parent
cd ..

# Create build directory
mkdir -p ${BUILD_DIR}

# Copy plugin files (INCLUDING vendor)
echo "Copying files..."
cp -r ${PLUGIN_SLUG} ${BUILD_DIR}/

# Remove excluded files/folders from build
cd ${BUILD_DIR}/${PLUGIN_SLUG}
rm -rf .git* tests phpunit.xml.dist composer.json composer.lock node_modules *.zip
find . -name ".DS_Store" -delete
cd ../..

# Verify vendor was copied
if [ ! -d "${BUILD_DIR}/${PLUGIN_SLUG}/vendor" ]; then
    echo "❌ Error: vendor folder not copied to build"
    exit 1
fi

# Create ZIP
echo "Creating ZIP..."
cd ${BUILD_DIR}
zip -r ../${ZIP_NAME} ${PLUGIN_SLUG}/
cd ..

# Cleanup
rm -rf ${BUILD_DIR}

# Verify ZIP contents
echo "Verifying ZIP contents..."
if unzip -l ${ZIP_NAME} | grep -q "vendor/yahnis-elsts"; then
    echo "✅ Build complete: ${ZIP_NAME}"
    echo "✅ Plugin Update Checker included"
    ls -lh ${ZIP_NAME}
else
    echo "❌ Warning: Plugin Update Checker not found in ZIP"
    exit 1
fi
