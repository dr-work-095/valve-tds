import { createIcons, Plus } from 'lucide';

createIcons({ icons: { Plus } });

const sidebar = document.getElementById('sidebar');
const mobileBackdrop = document.getElementById('mobileBackdrop');
const collapseButton = document.getElementById('collapseBtn');
const mobileMenuButton = document.getElementById('mobileMenuBtn');

collapseButton?.addEventListener('click', () => {
    const collapsed = sidebar.classList.toggle('collapsed');
    collapseButton.setAttribute('aria-expanded', String(!collapsed));
});

mobileMenuButton?.addEventListener('click', () => {
    sidebar.classList.add('mobile-open');
    mobileBackdrop.classList.remove('hidden');
    mobileMenuButton.setAttribute('aria-expanded', 'true');
});

mobileBackdrop?.addEventListener('click', () => {
    sidebar.classList.remove('mobile-open');
    mobileBackdrop.classList.add('hidden');
    mobileMenuButton.setAttribute('aria-expanded', 'false');
});

window.addEventListener('resize', () => {
    if (window.innerWidth >= 768) {
        sidebar?.classList.remove('mobile-open');
        mobileBackdrop?.classList.add('hidden');
        mobileMenuButton?.setAttribute('aria-expanded', 'false');
    }
});
