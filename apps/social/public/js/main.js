document.addEventListener('DOMContentLoaded', function () {
    // Theme Toggle Logic
    const themeToggle = document.getElementById('theme-toggle');
    if (themeToggle) {
        // Initialize icon
        const isLight = document.documentElement.classList.contains('light-mode');
        const icon = themeToggle.querySelector('i');
        if (icon) {
            icon.className = isLight ? 'fas fa-moon' : 'fas fa-sun';
        }

        themeToggle.addEventListener('click', () => {
            document.documentElement.classList.toggle('light-mode');
            const nowLight = document.documentElement.classList.contains('light-mode');
            localStorage.setItem('theme', nowLight ? 'light' : 'dark');
            document.cookie = `theme=${nowLight ? 'light' : 'dark'}; path=/; max-age=31536000`;

            // Update icon
            if (icon) {
                icon.className = nowLight ? 'fas fa-moon' : 'fas fa-sun';
            }
        });
    }

    const comingSoonLinks = document.querySelectorAll('.coming-soon');

    comingSoonLinks.forEach(link => {
        link.addEventListener('click', function (e) {
            e.preventDefault();

            // Create a simple toast notification
            const toast = document.createElement('div');
            toast.textContent = '🚀 This feature is coming soon!';
            toast.style.position = 'fixed';
            toast.style.bottom = '20px';
            toast.style.right = '20px';
            toast.style.backgroundColor = 'var(--accent-color)';
            toast.style.color = '#fff';
            toast.style.padding = '1rem 2rem';
            toast.style.borderRadius = '12px';
            toast.style.fontWeight = '700';
            toast.style.boxShadow = '0 10px 25px var(--accent-glow)';
            toast.style.zIndex = '10000';
            toast.style.animation = 'slideIn 0.3s ease-out';

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease-in forwards';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        });
    });
});

// Add animations to CSS via JS if needed, but better in style.css
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
