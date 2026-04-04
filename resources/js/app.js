// ── Global: Delete Modal ──
// Used on index (dynamic action), show, and edit (static action)

const deleteModal = document.getElementById('deleteModal');
if (deleteModal) {
    const deleteForm = document.getElementById('deleteForm');
    const modalText  = document.getElementById('modalText');

    // Called from index page buttons (dynamic per-row action URL + title)
    window.openDeleteModal = function(action, title) {
        if (deleteForm) deleteForm.action = action;
        if (modalText)  modalText.innerHTML = '<strong>' + title + '</strong> will be permanently deleted along with its file. This action cannot be undone.';
        deleteModal.classList.add('open');
    };

    // Called from show/edit pages (no arguments needed — form action is already set in blade)
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
