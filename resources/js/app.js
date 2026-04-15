import './bootstrap';
import TextPreprocessor from './text-preprocessor';
import './documents';

// ── 1. Register AI Preprocessor Globally ──
window.TextPreprocessor = TextPreprocessor;

// ── 2. Global UI Logic (Modals) ──
document.addEventListener('DOMContentLoaded', () => {
    const deleteModal = document.getElementById('deleteModal');

    if (deleteModal) {
        const deleteForm = document.getElementById('deleteForm');
        const modalText  = document.getElementById('modalText');

        // Dynamic Delete (Index Page - passing action and title)
        window.openDeleteModal = function(action, title) {
            if (deleteForm) deleteForm.action = action;
            if (modalText) {
                modalText.innerHTML = `<strong>${title}</strong> will be permanently deleted along with its file. This action cannot be undone.`;
            }
            deleteModal.classList.add('open');
        };

        // Static Delete (Show/Edit Page)
        window.openDeleteModalStatic = function() {
            deleteModal.classList.add('open');
        };

        window.closeDeleteModal = function() {
            deleteModal.classList.remove('open');
        };

        // Close on backdrop click
        deleteModal.addEventListener('click', e => {
            if (e.target === deleteModal) closeDeleteModal();
        });

        // Close on Escape key
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeDeleteModal();
        });
    }
});
