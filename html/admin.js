// Configuration
const API_URL = 'http://localhost/COS221/api.php';
const API_KEY = localStorage.getItem('apiKey') || '3e504e63ec49bdfaaba32a5bb993759b';

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

const productsContainer = document.getElementById('products-container');
const paginationContainer = document.getElementById('pagination');
const searchInput = document.getElementById('search');
const brandSelect = document.getElementById('brand');
const colorSelect = document.getElementById('color');
const sizeSelect = document.getElementById('size');
const sortSelect = document.getElementById('sort');
const limitSelect = document.getElementById('limit');
const applyFiltersBtn = document.getElementById('apply-filters');

document.addEventListener('DOMContentLoaded', () => {
    fetchProducts();
    
    applyFiltersBtn.addEventListener('click', () => {
        currentPage = 1;
        updateFilters();
        fetchProducts();
    });
    
    searchInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            currentPage = 1;
            updateFilters();
            fetchProducts();
        }
    });
});

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

async function fetchProducts() {
    try {
        productsContainer.innerHTML = '<div class="loading">Loading products...</div>';
        
        const requestData = {
            type: "GetAllProducts",
            apikey: API_KEY,
            return: [
                "Shoe_ID", "Name", "Brand_ID", "Color", "Size", "image_URL",
                "Release_Date", "Description", "Price", "buy_link"
            ],
            limit: filters.limit,
            offset: (currentPage - 1) * filters.limit,
            sort: filters.sort,
            order: filters.order
        };
        
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
        
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
            displayProducts(data.data);
            
            totalProducts = data.data.length * 3;
            
            updatePagination();
            
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
                    <a href="../view.php?Shoe_ID=${product.Shoe_ID}"> 
                        <img src="${product.image_URL || 'https://via.placeholder.com/300x200?text=No+Image'}" alt="${product.Name}">
                    </a>
                </div>
                <div class="product-info">
                    <h3 class="product-name">${product.Name || 'N/A'}</h3>
                    <p class="product-brand">Brand: ${product.Brand_ID || 'N/A'}</p>
                    <p>Color: ${product.Color || 'N/A'}</p>
                    <p>Size: ${product.Size || 'N/A'}</p>
                    <p class="product-description">${product.Description || 'No description available.'}</p>
                    <p class="product-price">Price: $${product.Price !== undefined ? product.Price : 'N/A'}</p>
                    ${product.buy_link ? `<a href="${product.buy_link}" class="buy-button" target="_blank">Buy Now</a>` : ''}
                    <button class="remove-button" onclick="removeProduct('${product.Shoe_ID}')">Remove</button>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    productsContainer.innerHTML = html;
}

function populateFilterOptions(products) {
    const brands = new Set();
    const colors = new Set();
    const sizes = new Set();
    
    products.forEach(product => {
        if (product.Brand_ID) brands.add(product.Brand_ID);
        if (product.Color) colors.add(product.Color);
        if (product.Size) sizes.add(product.Size);
    });
    
    Array.from(brands).sort().forEach(brand => {
        const option = document.createElement('option');
        option.value = brand;
        option.textContent = brand;
        brandSelect.appendChild(option);
    });
    
    Array.from(colors).sort().forEach(color => {
        const option = document.createElement('option');
        option.value = color;
        option.textContent = color;
        colorSelect.appendChild(option);
    });
    
    Array.from(sizes).sort((a, b) => a - b).forEach(size => {
        const option = document.createElement('option');
        option.value = size;
        option.textContent = size;
        sizeSelect.appendChild(option);
    });
}

async function removeProduct(shoeId) {
    if (!confirm('Are you sure you want to remove this product from the view? This will be undone on refresh.')) {
        return;
    }

    // Find and remove the product card from the DOM
    const productCard = document.querySelector(`.product-card [onclick="removeProduct('${shoeId}')"]`).closest('.product-card');
    if (productCard) {
        productCard.style.display = 'none';
        alert('Product removed !');
    }
}

function changePage(newPage) {
    if (newPage < 1 || newPage > Math.ceil(totalProducts / productsPerPage)) return;
    
    currentPage = newPage;
    fetchProducts();
    
    window.scrollTo({
        top: productsContainer.offsetTop - 20,
        behavior: 'smooth'
    });
}

function updatePagination() {
    const totalPages = Math.ceil(totalProducts / productsPerPage);
    
    if (totalPages <= 1) {
        paginationContainer.innerHTML = '';
        return;
    }
    
    let html = '';
    
    html += `<button onclick="changePage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>&laquo; Prev</button>`;
    
    const maxVisiblePages = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
    let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
    
    if (endPage - startPage + 1 < maxVisiblePages) {
        startPage = Math.max(1, endPage - maxVisiblePages + 1);
    }
    
    if (startPage > 1) {
        html += `<button onclick="changePage(1)">1</button>`;
        if (startPage > 2) {
            html += `<button disabled>...</button>`;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        html += `<button onclick="changePage(${i})" ${i === currentPage ? 'class="active"' : ''}>${i}</button>`;
    }
    
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            html += `<button disabled>...</button>`;
        }
        html += `<button onclick="changePage(${totalPages})">${totalPages}</button>`;
    }
    
    html += `<button onclick="changePage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>Next &raquo;</button>`;
    
    paginationContainer.innerHTML = html;
}