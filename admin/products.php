<?php
require_once '../config.php';

// Simple authentication
session_start();
$admin_password = 'admin123';

if (isset($_POST['login'])) {
    if ($_POST['password'] === $admin_password) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = 'Incorrect password';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

if (!isset($_SESSION['admin_logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Login - DK Flower Boutique</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 400px; margin: 100px auto; padding: 20px; }
            input { width: 100%; padding: 10px; margin: 10px 0; box-sizing: border-box; }
            button { width: 100%; padding: 10px; background: #f2cbd2; border: none; cursor: pointer; }
            .error { color: red; }
        </style>
    </head>
    <body>
        <h2>Admin Login</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Enter admin password" required>
            <button type="submit" name="login">Login</button>
        </form>
        <p><small>Default password: admin123</small></p>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - DK Flower Boutique</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .header { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { color: #f2cbd2; }
        .header-actions { display: flex; gap: 10px; }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #f2cbd2; color: white; }
        .btn-primary:hover { background: #e0b8c0; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .product-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .product-card img { width: 100%; height: 200px; object-fit: cover; border-radius: 4px; margin-bottom: 10px; }
        .product-card h3 { color: #333; margin-bottom: 10px; }
        .product-card .price { font-size: 20px; font-weight: bold; color: #f2cbd2; margin: 10px 0; }
        .product-card .category { color: #666; font-size: 14px; margin-bottom: 10px; }
        .product-card .actions { display: flex; gap: 10px; margin-top: 15px; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; }
        .modal-content { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 50px auto; max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h2 { color: #f2cbd2; }
        .close { font-size: 28px; font-weight: bold; cursor: pointer; color: #999; }
        .close:hover { color: #333; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; color: #333; font-weight: 600; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .form-group textarea { resize: vertical; min-height: 80px; }
        .form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
        .badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .badge-active { background: #28a745; color: white; }
        .badge-inactive { background: #dc3545; color: white; }
        .empty-state { text-align: center; padding: 40px; color: #999; }
        .search-bar { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .search-bar input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🌹 Product Management</h1>
        <div class="header-actions">
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            <button class="btn btn-primary" onclick="openAddModal()">+ Add New Product</button>
            <a href="?logout=1" class="btn btn-danger">Logout</a>
        </div>
    </div>

    <div class="search-bar">
        <input type="text" id="searchInput" placeholder="Search products by name..." onkeyup="filterProducts()">
    </div>

    <div id="productsContainer" class="products-grid">
        <!-- Products will be loaded here -->
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Product</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="productForm">
                <input type="hidden" id="productId">
                <div class="form-group">
                    <label for="productName">Product Name *</label>
                    <input type="text" id="productName" required>
                </div>
                <div class="form-group">
                    <label for="productImage">Image Path *</label>
                    <input type="text" id="productImage" placeholder="images/product.jpg" required>
                </div>
                <div class="form-group">
                    <label for="productCategory">Category</label>
                    <select id="productCategory">
                        <option value="">Select Category</option>
                        <option value="popular">Popular</option>
                        <option value="exotic">Exotic</option>
                        <option value="greens">Greens</option>
                        <option value="cactuses">Cactuses</option>
                        <option value="winter">Winter</option>
                        <option value="happy-birthday">Happy Birthday</option>
                        <option value="i-love-you">I Love You</option>
                        <option value="congratulations">Congratulations</option>
                        <option value="im-sorry">I'm Sorry</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="productPrice">Price ($) *</label>
                    <input type="number" id="productPrice" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label for="productStock">Stock Quantity</label>
                    <input type="number" id="productStock" min="0" value="0">
                </div>
                <div class="form-group">
                    <label for="productDescription">Description</label>
                    <textarea id="productDescription"></textarea>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let allProducts = [];

        // Load products on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();
        });

        // Load all products
        function loadProducts() {
            fetch('../api/products.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allProducts = data.data;
                        displayProducts(allProducts);
                    } else {
                        document.getElementById('productsContainer').innerHTML = 
                            '<div class="empty-state">No products found. Click "Add New Product" to get started.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    document.getElementById('productsContainer').innerHTML = 
                        '<div class="empty-state">Error loading products. Please try again.</div>';
                });
        }

        // Display products
        function displayProducts(products) {
            const container = document.getElementById('productsContainer');
            
            if (products.length === 0) {
                container.innerHTML = '<div class="empty-state">No products found. Click "Add New Product" to get started.</div>';
                return;
            }

            container.innerHTML = products.map(product => `
                <div class="product-card">
                    <img src="../${product.image}" alt="${product.name}" onerror="this.src='../images/placeholder.jpg'">
                    <h3>${product.name}</h3>
                    <div class="category">Category: ${product.category || 'N/A'}</div>
                    <div class="price">$${parseFloat(product.price).toFixed(2)}</div>
                    ${product.description ? `<p style="color: #666; font-size: 14px; margin: 10px 0;">${product.description}</p>` : ''}
                    <div style="margin: 10px 0;">
                        ${product.stock !== null ? `<span style="color: #666; font-size: 14px;">Stock: ${product.stock}</span>` : ''}
                    </div>
                    <div class="actions">
                        <button class="btn btn-primary" onclick="editProduct(${product.id})">Edit</button>
                        <button class="btn btn-danger" onclick="deleteProduct(${product.id}, '${product.name.replace(/'/g, "\\'")}')">Delete</button>
                    </div>
                </div>
            `).join('');
        }

        // Filter products
        function filterProducts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const filtered = allProducts.filter(product => 
                product.name.toLowerCase().includes(searchTerm)
            );
            displayProducts(filtered);
        }

        // Open add modal
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('productModal').style.display = 'block';
        }

        // Edit product
        function editProduct(id) {
            const product = allProducts.find(p => p.id == id);
            if (!product) return;

            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.name;
            document.getElementById('productImage').value = product.image;
            document.getElementById('productCategory').value = product.category || '';
            document.getElementById('productPrice').value = product.price;
            document.getElementById('productStock').value = product.stock || 0;
            document.getElementById('productDescription').value = product.description || '';
            document.getElementById('productModal').style.display = 'block';
        }

        // Close modal
        function closeModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        // Handle form submission
        document.getElementById('productForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const productId = document.getElementById('productId').value;
            const productData = {
                name: document.getElementById('productName').value,
                image: document.getElementById('productImage').value,
                category: document.getElementById('productCategory').value,
                price: parseFloat(document.getElementById('productPrice').value),
                stock: parseInt(document.getElementById('productStock').value) || 0,
                description: document.getElementById('productDescription').value
            };

            if (productId) {
                productData.id = parseInt(productId);
                updateProduct(productData);
            } else {
                createProduct(productData);
            }
        });

        // Create product
        function createProduct(data) {
            fetch('../api/products.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Product created successfully!');
                    closeModal();
                    loadProducts();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error creating product. Please try again.');
            });
        }

        // Update product
        function updateProduct(data) {
            fetch('../api/products.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Product updated successfully!');
                    closeModal();
                    loadProducts();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating product. Please try again.');
            });
        }

        // Delete product
        function deleteProduct(id, name) {
            if (!confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
                return;
            }

            fetch('../api/products.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id })
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    alert('Product deleted successfully!');
                    loadProducts();
                } else {
                    alert('Error: ' + result.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting product. Please try again.');
            });
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>

