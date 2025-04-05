import Imap from 'imap';
import { inspect } from 'util';
import dotenv from 'dotenv';
import { simpleParser } from 'mailparser';

dotenv.config();

export function fetchEmails(config, callback) {
    console.log('Starting email fetch process...');
    const imap = new Imap({
        user: config.user,
        password: config.password,
        host: config.host,
        port: config.port,
        tls: true
    });

    // Array to store results from all mailboxes
    let allEmails = [];
    // Mailboxes to check - INBOX and common spam folder names
    const mailboxes = ['INBOX', 'Spam', 'Junk', 'SPAM', 'JUNK'];
    
    // Process mailboxes one by one to avoid race conditions
    function processMailboxes(index) {
        if (index >= mailboxes.length) {
            // All mailboxes processed, close connection and return emails
            finishProcess();
            return;
        }

        const currentMailbox = mailboxes[index];
        console.log(`Attempting to open mailbox: ${currentMailbox}`);
        
        // Try to open the mailbox
        imap.openBox(currentMailbox, true, (err, box) => {
            if (err) {
                console.log(`Error opening ${currentMailbox}: ${err.message}`);
                // Continue with the next mailbox
                processMailboxes(index + 1);
                return;
            }

            console.log(`Successfully opened mailbox: ${currentMailbox}`);
            
            // Search for emails in this mailbox
            imap.search(['ALL'], (err, results) => {
                if (err) {
                    console.log(`Error searching ${currentMailbox}: ${err.message}`);
                    processMailboxes(index + 1);
                    return;
                }

                if (!results || !results.length) {
                    console.log(`No emails found in ${currentMailbox}`);
                    processMailboxes(index + 1);
                    return;
                }

                console.log(`Found ${results.length} emails in ${currentMailbox}`);
                
                // Process only the most recent 10 emails
                const recentResults = results.slice(-10);
                
                let fetchOptions = {
                    bodies: '',  // Fetch entire message
                    struct: true
                };
                
                let emailsFromMailbox = [];
                let fetch = imap.fetch(recentResults, fetchOptions);
                let emailProcessingPromises = []; // Track email processing promises
                
                fetch.on('message', (msg, seqno) => {
                    console.log(`Processing message #${seqno} from ${currentMailbox}`);
                    
                    let email = {
                        seqno,
                        mailbox: currentMailbox
                    };
                    
                    const processingPromise = new Promise((resolve) => {
                        msg.on('body', (stream) => {
                            simpleParser(stream)
                                .then(parsed => {
                                    email.header = {
                                        from: [parsed.from ? parsed.from.text : 'Unknown'],
                                        to: [parsed.to ? parsed.to.text : ''],
                                        subject: [parsed.subject || 'No Subject'],
                                        date: [parsed.date ? parsed.date.toISOString() : new Date().toISOString()]
                                    };

                                    // Behalte den gesamten Body (HTML, Text und TextAsHtml)
                                    email.body = {
                                        html: parsed.html || null,
                                        text: parsed.text || null,
                                        textAsHtml: parsed.textAsHtml || null
                                    };

                                    // Verarbeite Anhänge vollständig
                                    if (parsed.attachments && parsed.attachments.length > 0) {
                                        email.attachments = parsed.attachments.map(att => ({
                                            filename: att.filename,
                                            contentType: att.contentType,
                                            size: att.size,
                                            content: att.content.toString('base64') // Anhänge als Base64 speichern
                                        }));
                                    }

                                    emailsFromMailbox.push(email);
                                    resolve();
                                })
                                .catch(err => {
                                    console.error(`Error parsing email #${seqno}: ${err.message}`);
                                    emailsFromMailbox.push(email);
                                    resolve();
                                });
                        });
                    });
                    
                    emailProcessingPromises.push(processingPromise);
                });
                
                fetch.once('error', (err) => {
                    console.log(`Error fetching emails from ${currentMailbox}: ${err.message}`);
                });
                
                fetch.once('end', () => {
                    console.log(`Finished fetching emails from ${currentMailbox}`);
                    
                    // Wait for all email processing to complete
                    Promise.all(emailProcessingPromises).then(() => {
                        allEmails = [...allEmails, ...emailsFromMailbox];
                        processMailboxes(index + 1); // Process the next mailbox
                    });
                });
            });
        });
    }
    
    function finishProcess() {
        // Sort all emails by date (newest first)
        allEmails.sort((a, b) => {
            const dateA = a.header?.date?.[0] ? new Date(a.header.date[0]) : new Date(0);
            const dateB = b.header?.date?.[0] ? new Date(b.header.date[0]) : new Date(0);
            return dateB - dateA;
        });
        
        // Limit to the most recent 20 emails
        const limitedEmails = allEmails.slice(0, 20);
        
        console.log(`Total emails fetched across all mailboxes: ${allEmails.length}`);
        console.log(`Returning ${limitedEmails.length} most recent emails`);
        
        // Close the connection
        imap.end();
        
        // Return the emails via callback
        callback(null, limitedEmails);
    }
    
    // Set up IMAP connection event handlers
    imap.once('ready', () => {
        console.log('IMAP connection established');
        // Start processing mailboxes sequentially
        processMailboxes(0);
    });
    
    imap.once('error', (err) => {
        console.error(`IMAP connection error: ${err.message}`);
        callback(err);
    });
    
    imap.once('end', () => {
        console.log('IMAP connection ended');
    });
    
    // Connect to the mail server
    console.log('Connecting to mail server...');
    imap.connect();
}