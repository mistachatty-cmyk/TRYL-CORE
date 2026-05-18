const express = require('express');
const cors = require('cors');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 4000;

app.use(cors());

// Serve the public updates directory where zip files and manifests live
const updatesDir = path.join(__dirname, 'public', 'updates');

// Ensure directory exists
if (!fs.existsSync(updatesDir)) {
    fs.mkdirSync(updatesDir, { recursive: true });
}

// Serve static files (Zips and JSON manifests)
app.use('/updates', express.static(updatesDir));

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({ status: 'active', service: 'LokBridge OTA', version: '1.0.0' });
});

app.listen(PORT, () => {
    console.log(`🚀 LokBridge OTA Server running at http://localhost:${PORT}`);
    console.log(`📦 Serving updates from: /updates/`);
});
