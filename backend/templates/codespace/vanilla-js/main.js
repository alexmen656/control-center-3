// Welcome to [{[codespaceName]}]
console.log('Hello from [{[codespaceName]}]!');

// Vanilla JavaScript starter code
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    
    // Your code here
    const app = document.getElementById('app');
    if (app) {
        app.innerHTML = '<p>Your Vanilla JS app is running!</p>';
    }
});
