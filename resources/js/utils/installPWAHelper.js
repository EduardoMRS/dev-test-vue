// Global variable to store the deferred prompt
let deferredPrompt = null;
let isInstallPromptAvailable = false;

// Initialize the PWA install helper
export function initPWAInstaller() {
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        isInstallPromptAvailable = true;
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt = null;
        isInstallPromptAvailable = false;
    });
}

export function checkIsInstall() {
    // Check if running in standalone mode (already installed)
    if (window.matchMedia('(display-mode: standalone)').matches || 
        window.matchMedia('(display-mode: minimal-ui)').matches ||
        window.navigator.standalone === true) {
        return true;
    }

    // Check if user already refused the prompt recently
    try {
        const promptShown = localStorage.getItem('installPromptShown');
        if (promptShown) {
            const timestamp = parseInt(promptShown);
            const now = Date.now();
            const sevenDays = 7 * 24 * 60 * 60 * 1000;
            
            if (now - timestamp < sevenDays) {
                return true; // Don't show again within 7 days
            } else {
                // Remove expired entry
                localStorage.removeItem('installPromptShown');
            }
        }
    } catch (e) {
        if(process.env.NODE_ENV === 'development') console.warn('Error checking localStorage:', e);
    }

    return false;
}

export function promptInstall() {    
    if (!deferredPrompt || !isInstallPromptAvailable) {
        return false;
    }

    // Show the install prompt
    deferredPrompt.prompt();
    
    // Wait for the user to respond to the prompt
    deferredPrompt.userChoice.then((choiceResult) => {
        
        if (choiceResult.outcome === 'accepted') {
        } else {
            recusedPrompt();
        }
        
        // Clear the deferred prompt
        deferredPrompt = null;
        isInstallPromptAvailable = false;
    });

    return true;
}

export function checkIOS(){
    const userAgent = window.navigator.userAgent.toLowerCase();
    return /iphone|ipad|ipod/.test(userAgent);
}

export function recusedPrompt() {
    try {
        localStorage.setItem('installPromptShown', Date.now().toString());
    } catch (e) {
        if(process.env.NODE_ENV === 'development') console.warn('Error saving to localStorage:', e);
        // Fallback to cookie if localStorage fails
        try {
            if (typeof cookieStore !== 'undefined') {
                cookieStore.set('installPromptShown', 'true', { expires: Date.now() + 7 * 24 * 60 * 60 * 1000 });
            } else {
                // Manual cookie fallback
                document.cookie = `installPromptShown=true; expires=${new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toUTCString()}; path=/`;
            }
        } catch (cookieError) {
            if(process.env.NODE_ENV === 'development') console.warn('Error setting cookie:', cookieError);
        }
    }
}

export function isInstallPromptReady() {
    return isInstallPromptAvailable && deferredPrompt !== null;
}