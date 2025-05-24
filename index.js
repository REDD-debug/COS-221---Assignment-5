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

// Fetch products from API
async function fetchProducts() {
    try {
        // Show loading state
        productsContainer.innerHTML = '<div class="loading">Loading products...</div>';
        
        // Prepare request data
        const requestData = {
            type: "GetAllProducts",
            apikey: API_KEY,
            return: [
                "Shoe_ID", "Name", "Brand_ID", "Color", "Size", "image_URL",
                "Release_Date", "Description", "Price", "buy_link" // <-- Added Price here
            ],
            limit: filters.limit,
            offset: (currentPage - 1) * filters.limit,
            sort: filters.sort,
            order: filters.order
        };
        
        // Add search filters if they exist
        if (filters.search) {
            requestData.search = {
                Name: filters.search
            };
            requestData.fuzzy = true;
        }
        
        if (filters.brand) {
            if (!requestData.search) requestData.search = {};
            requestData.search.Brand_ID = filters.brand;
        }
        
        if (filters.color) {
            if (!requestData.search) requestData.search = {};
            requestData.search.Color = filters.color;
        }
        
        if (filters.size) {
            if (!requestData.search) requestData.search = {};
            requestData.search.Size = filters.size;
        }
        
        // Make API request
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            // Display products
            displayProducts(data.data);
            
            // Update total products count (this would come from the API)
            totalProducts = data.data.length * 3; // Placeholder - we would should get the actual total from our API
            
            // Update pagination
            updatePagination();
            
            // Populate filter options if not already done
            if (brandSelect.options.length <= 1) {
                populateFilterOptions(data.data);
            }
        } else {
            throw new Error(data.message || 'Failed to fetch products');
        }
    } catch (error) {
        console.error('Error fetching products:', error);
        productsContainer.innerHTML = `<div class="error">Error loading products: ${error.message}</div>`;
    }
}

// Display products in the grid
function displayProducts(products) {
    if (products.length === 0) {
        productsContainer.innerHTML = '<div class="error">No products found matching your criteria.</div>';
        return;
    }

    let html = '<div class="products-grid">';

    products.forEach(product => {
        html += `
            <div class="product-card">
                <div class="product-image">
                <img src="${product.image_URL || 'https://via.placeholder.com/300x200?text=No+Image'}" alt="${product.Name}">
                </div>
                <div class="product-info">
                    <h3 class="product-name">${product.Name}</h3>
                    <p class="product-brand">Brand: ${product.Brand_ID || 'N/A'}</p>
                    <p>Color: ${product.Color || 'N/A'}</p>
                    <p>Size: ${product.Size || 'N/A'}</p>
                    <p class="product-description">${product.Description || 'No description available.'}</p>
                    <p class="product-price">Price: $${product.Price !== undefined ? product.Price : 'N/A'}</p>
                    ${product.buy_link ? `<a href="${product.buy_link}" class="buy-button" target="_blank">Buy Now</a>` : ''}
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    productsContainer.innerHTML = html;
}


// Populate filter dropdowns with available options
function populateFilterOptions(products) {
    const brands = new Set();
    const colors = new Set();
    const sizes = new Set();
    
    products.forEach(product => {
        if (product.Brand_ID) brands.add(product.Brand_ID);
        if (product.Color) colors.add(product.Color);
        if (product.Size) sizes.add(product.Size);
    });
    
    // Populate brand filter
    Array.from(brands).sort().forEach(brand => {
        const option = document.createElement('option');
        option.value = brand;
        option.textContent = brand;
        brandSelect.appendChild(option);
    });
    
    // Populate color filter
    Array.from(colors).sort().forEach(color => {
        const option = document.createElement('option');
        option.value = color;
        option.textContent = color;
        colorSelect.appendChild(option);
    });
    
    // Populate size filter
    Array.from(sizes).sort((a, b) => a - b).forEach(size => {
        const option = document.createElement('option');
        option.value = size;
        option.textContent = size;
        sizeSelect.appendChild(option);
    });
}
