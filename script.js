// Fail-safe script redirect for older references
const script = document.createElement('script');
script.src = 'assets/js/script.js';
document.head.appendChild(script);
