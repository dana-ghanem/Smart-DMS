// public/js/documents.js

// MOCK DATA - Work with this until real API is ready
let mockDocuments = [
    { id: 1, title: "Research Paper on AI", author: "Dr. Smith", category: "Research", date: "2024-01-15" },
    { id: 2, title: "Annual Report 2024", author: "John Doe", category: "Reports", date: "2024-02-20" },
    { id: 3, title: "Student Handbook", author: "Admin", category: "Academic", date: "2024-03-01" },
    { id: 4, title: "Machine Learning Basics", author: "Prof. Lee", category: "Research", date: "2024-03-10" },
    { id: 5, title: "Budget Proposal", author: "Finance Dept", category: "Administrative", date: "2024-03-15" },
];

let currentDocuments = [...mockDocuments];

// Render table function
function renderTable() {
    const tbody = document.getElementById('documentTableBody');
    tbody.innerHTML = '';
    
    currentDocuments.forEach(doc => {
        const row = `
            <tr>
                <td>${doc.id}</td>
                <td>${doc.title}</td>
                <td>${doc.author}</td>
                <td>${doc.category}</td>
                <td>${doc.date}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="viewDocument(${doc.id})">View</button>
                    <button class="btn btn-sm btn-warning" onclick="editDocument(${doc.id})">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteDocument(${doc.id})">Delete</button>
                </td>
            </tr>
        `;
        tbody.innerHTML += row;
    });
}

// Delete function (will connect to real API later)
function deleteDocument(id) {
    if(confirm('Are you sure you want to delete this document?')) {
        // TODO: Will replace with API call when Student B's endpoint is ready
        console.log('Delete document:', id);
        mockDocuments = mockDocuments.filter(doc => doc.id !== id);
        currentDocuments = [...mockDocuments];
        renderTable();
        alert('Document deleted (mock)');
    }
}

// Initialize page
renderTable();
// Add to your documents.js

// Search function
document.getElementById('searchInput')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    currentDocuments = mockDocuments.filter(doc => 
        doc.title.toLowerCase().includes(searchTerm) || 
        doc.author.toLowerCase().includes(searchTerm)
    );
    renderTable();
});

// Category filter
function populateCategoryFilter() {
    const categories = [...new Set(mockDocuments.map(doc => doc.category))];
    const select = document.getElementById('categoryFilter');
    categories.forEach(cat => {
        select.innerHTML += `<option value="${cat}">${cat}</option>`;
    });
}

document.getElementById('categoryFilter')?.addEventListener('change', function(e) {
    const category = e.target.value;
    if(category) {
        currentDocuments = mockDocuments.filter(doc => doc.category === category);
    } else {
        currentDocuments = [...mockDocuments];
    }
    renderTable();
});

// Call this after mock data is defined
populateCategoryFilter();