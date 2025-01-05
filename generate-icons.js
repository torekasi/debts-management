const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const sizes = [72, 96, 128, 144, 152, 192, 384, 512];
const iconPath = path.join(__dirname, 'icons', 'icon.png');
const iconDir = path.join(__dirname, 'icons');

// Ensure icons directory exists
if (!fs.existsSync(iconDir)) {
    fs.mkdirSync(iconDir);
}

// Generate regular icons
sizes.forEach(size => {
    sharp(iconPath)
        .resize(size, size)
        .toFile(path.join(iconDir, `icon-${size}x${size}.png`))
        .then(() => console.log(`Generated ${size}x${size} icon`))
        .catch(err => console.error(`Error generating ${size}x${size} icon:`, err));
});

// Generate maskable icon with padding
sharp(iconPath)
    .resize(512, 512, {
        fit: 'contain',
        background: { r: 79, g: 70, b: 229, alpha: 1 } // Indigo-600
    })
    .toFile(path.join(iconDir, 'maskable-icon.png'))
    .then(() => console.log('Generated maskable icon'))
    .catch(err => console.error('Error generating maskable icon:', err));
