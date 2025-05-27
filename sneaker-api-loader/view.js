// Get Shoe_ID from URL query parameter
const urlParams = new URLSearchParams(window.location.search);
const shoeId = urlParams.get('Shoe_ID');
const apiUrl = 'api.php'; // Corrected to match file structure (same directory as view.php)
const apiKey = '7df553402e530adbc1516099cf00c527'; 

// Full-page loading screen
function createFullPageLoadingText() {
    const loadingScreen = document.createElement('div');
    loadingScreen.id = 'loading-screen';
    loadingScreen.classList.add('loading');
    loadingScreen.textContent = 'Loading...';
    document.body.appendChild(loadingScreen);
    loadingScreen.style.visibility = 'visible';
}

function hideFullPageLoadingText() {
    const loadingScreen = document.getElementById('loading-screen');
    if (loadingScreen) {
        setTimeout(() => {
            loadingScreen.style.visibility = 'hidden';
            loadingScreen.remove();
        }, 300);
    }
}

// Fetch product data
function fetchProductData(shoeId) {
    createFullPageLoadingText();

    const xhr = new XMLHttpRequest();
    xhr.open('POST', apiUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    const requestData = JSON.stringify({
        type: 'GetAllProducts',
        apikey: apiKey,
        search: {
            Shoe_ID: shoeId
        },
        return: [
            'Shoe_ID', 'Name', 'Description', 'Brand_ID', 'Color', 'Size',
            'image_URL', 'Release_Date', 'Price', 'buy_link', 'AverageRating', 'Reviews'
        ],
        limit: 1
    });

    xhr.onload = function () {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.status === 'success' && response.data.length > 0) {
                displayProductDetails(response.data[0]);
            } else {
                console.error('No product data found:', response.message);
                displayError('Product not found');
            }
            hideFullPageLoadingText();
        } else {
            console.error('API request failed:', xhr.status, xhr.statusText);
            displayError('Failed to load product data');
            hideFullPageLoadingText();
        }
    };

    xhr.onerror = function () {
        console.error('Request failed');
        displayError('Network error occurred');
        hideFullPageLoadingText();
    };

    xhr.send(requestData);
}

// Display error message
function displayError(message) {
    const container = document.querySelector('.container');
    const errorDiv = document.createElement('div');
    errorDiv.classList.add('error');
    errorDiv.textContent = message;
    container.innerHTML = '';
    container.appendChild(errorDiv);
}

// Display product details
function displayProductDetails(product) {
    // Populate product info
    document.querySelector('#product-name').textContent = product.Name || 'N/A';
    document.querySelector('#product-description').textContent = product.Description || 'No description available';
    document.querySelector('#product-price').textContent = product.Price ? `$ ${product.Price} ` : 'Price not available';
    document.querySelector('#product-rating').textContent = product.AverageRating ? `${product.AverageRating}/5` : 'No rating';
    document.querySelector('#product-review').textContent = product.Reviews && product.Reviews.length > 0 ? product.Reviews.join('; ') : 'No reviews available';
    document.querySelector('#product-buy-link').href = product.buy_link || '#';
    document.querySelector('#product-buy-link').textContent = product.buy_link ? 'Visit Store' : 'No store link available';
    document.querySelector('#product-brand').textContent = product.Brand_ID || 'N/A';
    document.querySelector('#product-color').textContent = product.Color || 'N/A';
    document.querySelector('#product-size').textContent = product.Size || 'N/A';
    document.querySelector('#product-release-date').textContent = product.Release_Date || 'N/A';

    // Handle carousel images
    const carouselImagesContainer = document.querySelector('.carousel-images');
    const prevButton = document.querySelector('.prev');
    const nextButton = document.querySelector('.next');
    let imageUrls = [];
    
    // Parse image_URL (assuming it's a JSON string or single URL)
    try {
        imageUrls = product.image_URL ? JSON.parse(product.image_URL) : [product.image_URL];
    } catch (e) {
        imageUrls = product.image_URL ? [product.image_URL] : [];
    }

    let currentIndex = 0;

    if (imageUrls && imageUrls.length > 0) {
        carouselImagesContainer.innerHTML = '';
        imageUrls.forEach((url, index) => {
            const img = document.createElement('img');
            img.src = url;
            img.alt = `Product Image ${index + 1}`;
            if (index === 0) img.classList.add('active');
            carouselImagesContainer.appendChild(img);
        });
    } else {
        carouselImagesContainer.innerHTML = '<p>No images available</p>';
    }

    // Carousel navigation
    function updateActiveImage() {
        const images = carouselImagesContainer.querySelectorAll('img');
        images.forEach((img, index) => {
            img.classList.remove('active');
            if (index === currentIndex) img.classList.add('active');
        });
    }

    prevButton.addEventListener('click', () => {
        if (currentIndex > 0) {
            currentIndex--;
        } else {
            currentIndex = imageUrls.length - 1;
        }
        updateActiveImage();
    });

    nextButton.addEventListener('click', () => {
        if (currentIndex < imageUrls.length - 1) {
            currentIndex++;
        } else {
            currentIndex = 0;
        }
        updateActiveImage();
    });
}

// Handle Rating/Review Modal
const modal = document.getElementById('rating-review-modal');
const addRatingReviewLink = document.getElementById('add-rating-review-link');
const closeModal = document.getElementById('close-modal');
const submitButton = document.getElementById('submit-rating-review');
const ratingInput = document.getElementById('rating-score');
const reviewInput = document.getElementById('review-comment');

// Open modal
addRatingReviewLink.addEventListener('click', (e) => {
    e.preventDefault();
    modal.style.display = 'flex';
});

// Close modal
closeModal.addEventListener('click', () => {
    modal.style.display = 'none';
    resetForm();
});

// Close modal when clicking outside
window.addEventListener('click', (e) => {
    if (e.target === modal) {
        modal.style.display = 'none';
        resetForm();
    }
});

// Reset form
function resetForm() {
    ratingInput.value = '1';
    reviewInput.value = '';
}

// Submit rating/review
submitButton.addEventListener('click', () => {
    const rating = parseInt(ratingInput.value);
    const review = reviewInput.value.trim();

    // Basic validation
    if (rating < 1 || rating > 5) {
        alert('Please select a rating between 1 and 5.');
        return;
    }

    if (review.length > 100) {
        alert('Review must be 100 characters or less.');
        return;
    }

    // Hardcoding UserID for now (since authentication isn't fully implemented)
    const userId = 'U105'; // Replace with actual user ID from session after login implementation

    const requestData = {
        type: 'AddRatingReview',
        apikey: apiKey,
        user_id: userId,
        shoe_id: shoeId,
        rating: rating,
        review: review || null // Send null if review is empty
    };

    const xhr = new XMLHttpRequest();
    xhr.open('POST', apiUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/json');

    xhr.onload = function () {
        if (xhr.status === 200) {
            const response = JSON.parse(xhr.responseText);
            if (response.status === 'success') {
                alert('Rating and review submitted successfully!');
                modal.style.display = 'none';
                resetForm();
                // Refresh product data to show updated rating/review
                fetchProductData(shoeId);
            } else {
                alert('Error: ' + response.message);
            }
        } else {
            alert('Failed to submit rating/review. Please try again.');
        }
    };

    xhr.onerror = function () {
        alert('Network error occurred. Please try again.');
    };

    xhr.send(JSON.stringify(requestData));
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', function () {
    if (shoeId) {
        fetchProductData(shoeId);
    } else {
        console.error('No Shoe_ID in URL');
        displayError('No product ID provided');
        hideFullPageLoadingText();
    }
});