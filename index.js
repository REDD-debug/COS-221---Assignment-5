// Configuration
const API_URL = 'http://localhost/COS221/api.php';
const API_KEY = '01f7356a50fcdef2ddef37c336dae321';

// Global variables
let currentPage = 1;
let totalProducts = 0;
let productsPerPage = 12;
let filters = {
    search: '',
    brand: '',
    color: '',
    size: '',
    sort: 'Name',
    limit: 12
};

// DOM elements
const productsContainer = document.getElementById('products-container');
const paginationContainer = document.getElementById('pagination');
const searchInput = document.getElementById('search');
const brandSelect = document.getElementById('brand');
const colorSelect = document.getElementById('color');
const sizeSelect = document.getElementById('size');
const sortSelect = document.getElementById('sort');
const limitSelect = document.getElementById('limit');
const applyFiltersBtn = document.getElementById('apply-filters');

// Initialize the page
document.addEventListener('DOMContentLoaded', () => {
    // Load products
    fetchProducts();
    
    // Set up event listeners
    applyFiltersBtn.addEventListener('click', () => {
        currentPage = 1;
        updateFilters();
        fetchProducts();
    });
    
    // Allow Enter key to apply filters
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            currentPage = 1;
            updateFilters();
            fetchProducts();
        }
    });
});

// Update filters from form inputs
function updateFilters() {
    filters = {
        search: searchInput.value.trim(),
        brand: brandSelect.value,
        color: colorSelect.value,
        size: sizeSelect.value,
        sort: sortSelect.value.includes('DESC') ? sortSelect.value.split(' ')[0] : sortSelect.value,
        order: sortSelect.value.includes('DESC') ? 'DESC' : 'ASC',
        limit: parseInt(limitSelect.value)
    };
    
    productsPerPage = filters.limit;
}
