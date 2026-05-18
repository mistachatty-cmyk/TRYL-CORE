const fs = require('fs');
const path = require('path');
const archiver = require('archiver');

const pluginFolder = process.argv[2] || 'tryl-ecommerce-core';
const sourceDir = path.join(__dirname, '..', pluginFolder);
const publicDir = path.join(__dirname, 'public', 'updates');

if (!fs.existsSync(sourceDir)) {
    console.error(`❌ Source directory not found: ${sourceDir}`);
    process.exit(1);
}

if (!fs.existsSync(publicDir)) {
    fs.mkdirSync(publicDir, { recursive: true });
}

// Extract version from main plugin file
const mainPluginFile = path.join(sourceDir, `${pluginFolder}.php`);
let version = '1.0.0';

if (fs.existsSync(mainPluginFile)) {
    const content = fs.readFileSync(mainPluginFile, 'utf8');
    const versionMatch = content.match(/Version:\s*([0-9.]+)/i);
    if (versionMatch && versionMatch[1]) {
        version = versionMatch[1];
    }
} else {
    console.warn(`⚠️ Main plugin file not found: ${mainPluginFile}. Using default version ${version}.`);
}

const zipFileName = `${pluginFolder}-${version}.zip`;
const zipFilePath = path.join(publicDir, zipFileName);

// Base URL for the OTA server (can be overridden via ENV or configured later)
const serverBaseUrl = process.env.LOKBRIDGE_URL || 'http://localhost:4000';

console.log(`📦 Packaging ${pluginFolder} v${version}...`);

const output = fs.createWriteStream(zipFilePath);
const archive = archiver('zip', { zlib: { level: 9 } });

output.on('close', () => {
    console.log(`✅ Successfully created ${zipFileName} (${archive.pointer()} bytes)`);

    // Generate JSON Manifest
    const manifest = {
        name: "TRYL Premium E-Commerce Core",
        slug: pluginFolder,
        version: version,
        author: "EHDesigns / LokServices",
        download_url: `${serverBaseUrl}/updates/${zipFileName}`,
        requires: "6.0",
        tested: "6.5",
        last_updated: new Date().toISOString().replace('T', ' ').split('.')[0],
        sections: {
            description: "Premium TRYL core engine handling custom cart, checkout, and global UI.",
            changelog: "<ul><li>Automated OTA deployment via LokBridge.</li></ul>"
        }
    };

    const manifestPath = path.join(publicDir, 'tryl-core.json');
    fs.writeFileSync(manifestPath, JSON.stringify(manifest, null, 4));
    console.log(`✅ Updated manifest at ${manifestPath}`);
    console.log(`\n🚀 LokBridge Deployment Complete!`);
    console.log(`Test sites pinging ${serverBaseUrl}/updates/tryl-core.json will now prompt for update to v${version}.`);
});

archive.on('error', (err) => { throw err; });

archive.pipe(output);

// Recursively add files to zip, skipping large node_modules and dot-files at the directory level to prevent severe performance bottlenecks on Windows
function addDirectoryToZip(dirPath, zipPrefix) {
    const items = fs.readdirSync(dirPath);
    for (const item of items) {
        const fullPath = path.join(dirPath, item);
        const relativePath = path.join(zipPrefix, item).replace(/\\/g, '/'); // Ensure standard cross-platform zip slashes
        
        // Skip ignored directories/files immediately to bypass heavy sub-folders
        if (item === 'node_modules' || item === '.git' || item === '.github' || item === '.env' || item.endsWith('.zip')) {
            continue;
        }
        
        const stat = fs.statSync(fullPath);
        if (stat.isDirectory()) {
            addDirectoryToZip(fullPath, relativePath);
        } else {
            archive.file(fullPath, { name: relativePath });
        }
    }
}

addDirectoryToZip(sourceDir, pluginFolder);
archive.finalize();
