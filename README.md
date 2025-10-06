# 🛒 E-Commerce Website (PHP & MySQL)

A complete PHP eCommerce system with user frontend and admin dashboard for managing products, categories, and orders.

---

## 🚀 Features

### 🧑‍💼 Admin Panel
- Secure admin login (email/password)
- Manage categories (Add, Edit, Delete)
- Manage products (Name, Price, Description, Image, Stock)
- Manage orders (Update status: Process → Shipped → Delivered)

### 👨‍🛍️ User Frontend
- User registration & login
- Product catalog with search, sort, and category filter
- Product details page
- Shopping cart & wishlist
- Checkout with address selection
- Order tracking dashboard

---

## Download the Code

Download the code from github or clone the project repository 

---

## ⚙️ Installation Guide

### Step 1: Setup Database
1. Open **phpMyAdmin**
2. Create the database:
   ```sql
   CREATE DATABASE ecommerce_db;
Import your SQL schema (tables + sample data)
(Usually named ecommerce_db.sql)

### Step 2: Configure Database

Edit config.php

### Step 3: Setup Files

Copy project folder to:

XAMPP → C:/xampp/htdocs/ecommerce/


### Step 4: Default Login

Admin Panel

URL: http://localhost/ecommerce/admin/

Email: admin@ecommerce.com

Password: admin123

User Frontend

URL: http://localhost/ecommerce/

Register a new account to start shopping.

## 🔒 Security

Password hashing (password_hash())

Prepared statements for SQL

Session-based authentication

Role-based access (Admin/User)

## 🧩 Troubleshooting

Images not loading: Check /uploads/ permissions (chmod 755)

Database errors: Verify credentials in config.php

404 issues: Check BASE_URL path

##

