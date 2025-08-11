// Welcome to [{[codespaceName]}]
console.log('Hello from [{[codespaceName]}]!');

const express = require('express');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

// Serve static files
app.use(express.static(path.join(__dirname, 'public')));
app.use(express.json());

// Routes
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'index.html'));
});

app.get('/api/hello', (req, res) => {
    res.json({ 
        message: 'Hello from [{[codespaceName]}] API!',
        timestamp: new Date().toISOString()
    });
});

app.listen(PORT, () => {
    console.log(`Server running on http://localhost:${PORT}`);
});
