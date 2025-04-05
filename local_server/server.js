import express from 'express';
import cors from 'cors';
import { fetchEmails } from './load.js';

const app = express();
const port = 3000;

// Enable CORS for all routes
app.use(cors());
app.use(express.json()); // Parse JSON bodies

// Cache for emails
let emailCache = {
    data: null,
    timestamp: 0,
    inProgress: false
};

// Cache expires after 5 minutes
const CACHE_TTL = 5 * 60 * 1000;

app.post('/emails', (req, res) => {
    const { host, port, user, password } = req.body;

    if (!host || !port || !user || !password) {
        return res.status(400).send('Missing required configuration parameters');
    }

    const now = Date.now();

    // If cache is fresh, return cached data
    if (emailCache.data && (now - emailCache.timestamp < CACHE_TTL)) {
        console.log('Returning cached emails');
        return res.json(emailCache.data);
    }

    // If a fetch is already in progress, wait for it
    if (emailCache.inProgress) {
        console.log('Email fetch already in progress, waiting...');
        const checkInterval = setInterval(() => {
            if (!emailCache.inProgress) {
                clearInterval(checkInterval);
                res.json(emailCache.data);
            }
        }, 500);

        setTimeout(() => {
            clearInterval(checkInterval);
            if (!res.headersSent) {
                if (emailCache.data) {
                    res.json(emailCache.data); // Return stale data if available
                } else {
                    res.status(504).send('Timeout while fetching emails');
                }
            }
        }, 30000); // 30 second timeout

        return;
    }

    // Start new fetch
    emailCache.inProgress = true;
    console.log('Fetching new emails from server...');

    fetchEmails({ host, port, user, password }, (err, emails) => {
        emailCache.inProgress = false;

        if (err) {
            console.error('Error fetching emails:', err);

            if (emailCache.data) {
                res.setHeader('X-Data-Source', 'stale');
                res.json(emailCache.data);
            } else {
                res.status(500).send(err.toString());
            }
            return;
        }

        // Update cache
        emailCache.data = emails;
        emailCache.timestamp = Date.now();

        // Send response
        res.json(emails);
    });
});

app.get('/emails/refresh', (req, res) => {
    emailCache = {
        data: null,
        timestamp: 0,
        inProgress: false
    };
    res.redirect('/emails');
});

app.listen(port, () => {
    console.log(`Server läuft auf Port ${port}`);
});